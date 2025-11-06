<?php

	namespace App\View\Compilers;

	use App\View\Compilers\scheme\CompilerException;
	use App\View\Compilers\scheme\ViewsInterface;
	use Exception;
	use Closure;
	use Error;
	use ReflectionException;

	/**
	 * The Blade class provides a lightweight template engine with support
	 * for registering custom directives and compiling template content.
	 *
	 * It serves as a simplified reimplementation of Laravel's Blade engine,
	 * enabling dynamic PHP content compilation, safe capturing, and directive extensions.
	 */
	final class Blade
	{
		/**
		 * Holds the compiler implementation for directives and wrapping.
		 *
		 * @var ViewsInterface
		 */
		private ViewsInterface $compiler;

		/**
		 * Holds all registered compiler instances used for directive parsing.
		 *
		 * @var ViewsInterface[]
		 */
		private static array $instances = [];

		/**
		 * Holds the recent paths rendered via the load() function for tracking and error reporting.
		 *
		 * @var string[]
		 */
		private static array $tracePaths = [];

		/**
		 * Loads and compiles a view template from the specified file path,
		 * then captures and renders it with the provided data.
		 *
		 * The path is added to the internal trace paths for error tracking.
		 *
		 * @param string $path The file path of the view template to load.
		 * @param array $extract An associative array of variables to extract into the view.
		 *
		 * @return string The final rendered and compiled view content.
		 *
		 * @throws CompilerException|ReflectionException If the file does not exist or if there is a compilation/rendering error.
		 */
		public static function load(string $path, array $extract = []): string {
			/**
			 * Steps:
			 * 1. [load] require path = Load the content from the given path.
			 * 2. [compile] compile content = Apply all registered regex patterns and directives.
			 * 3. [capture] execute compiled content = Execute the compiled content and handle PHP execution safely.
			 */
			$realPath = self::resolveCaseInsensitivePath($path);
			if (!$realPath) {
				throw new CompilerException("File $path does not exist");
			}

			self::$tracePaths[] = $realPath;

			$isAssociativeArray = function(array $arr): bool {
				foreach (array_keys($arr) as $key) {
					if (is_string($key)) return true;
				}
				return false;
			};

			if ($extract && !$isAssociativeArray($extract)) {
				throw new CompilerException("Invalid data passed for extraction: " . json_encode($extract));
			}

			return self::compileAndCapture(file_get_contents($realPath), $extract);
		}

		/**
		 * Compiles the given template content using all registered compiler instances.
		 *
		 * Automatically loads directive files if no compilers have been registered yet.
		 *
		 * @param string $content The raw template content to compile.
		 * @param bool $basicOnly Whether to perform only basic compilation (default false).
		 *
		 * @return string The compiled PHP-ready content.
		 *
		 * @throws CompilerException|ReflectionException
		 */
		public static function compile(string $content, bool $basicOnly = false): string {
			if (empty(self::$instances))
				self::loadDirectives(__DIR__ . '/../directives');

			$buffer = new Buffer();
			foreach (self::$instances as $compiler) {
				$buffer->registerWrap($compiler->getWrapper());
				$buffer->registerDirectives($compiler->getDirectives());
				$buffer->registerAdvanceDirectives($compiler->getAdvanceDirectives());
				$buffer->registerSequences($compiler->getSequence());
			}

			return $buffer->compile($content, $basicOnly);
		}

		/**
		 * Captures and executes the compiled content within a sandboxed environment.
		 *
		 * Extracts all provided variables into scope and includes the compiled PHP file.
		 * If an error or exception occurs, it formats an HTML-based error output
		 * with trace details and throws it as a CompilerException.
		 *
		 * @param string $content The compiled PHP content to execute.
		 * @param array $data Variables to extract into scope during execution.
		 *
		 * @return void
		 *
		 * @throws CompilerException If an error occurs during content execution.
		 */
		private static function capture(string $content, array $data = []): void
		{
			static $reported = false;
			static $errorTraces = [];

			$tempFile = tempnam(sys_get_temp_dir(), 'tpl_') . '.php';
			$realPath = $tempFile;

			try {
				$__resolvedPath = $tempFile;
				file_put_contents($tempFile, $content);

				(static function () use ($tempFile, $data, &$__resolvedPath) {
					if (self::$tracePaths) {
						$__resolvedPath = end(self::$tracePaths);
					}
					extract($data, EXTR_SKIP);
					include $tempFile;
				})();
			} catch (Exception|Error $e) {
				if (empty($errorTraces)) {
					$traces = $e->getTrace();
					$originalTrace = [
						'path' 	   => $__resolvedPath,
						'message'  => $e->getMessage(),
						'code'     => $e->getCode(),
						'file'     => $e->getFile(),
						'line'     => $e->getLine(),
						'previous' => $e->getPrevious()?->getMessage(),
					];

					array_unshift($traces, $originalTrace);
					$errorTraces = [
						'path' => $__resolvedPath,
						'tempPath' => $realPath,
						'message' => $e->getMessage(),
						'line' => $e->getLine(),
						'code' => $e->getCode(),
						'traces' => $traces
					];
				}
			} finally {
				unlink($tempFile);
			}

			if (!empty($errorTraces) && !$reported) {
				$errorTrace = $errorTraces;
				$errorLogsHtml = '';

				foreach ($errorTrace['traces'] as $trace) {
					if (($trace['file'] ?? '') == $tempFile) {
						$errorTrace['line'] = $trace['line'];
						break;
					}
				}

				foreach ($errorTrace['traces'] as $i => $log) {
					$call = isset($log['function'])
						? ($log['class'] ?? '') . ($log['type'] ?? '') . $log['function'] . '()'
						: '[no function]';

					$file = $log['file'] ?? 'N/A';
					$line = $log['line'] ?? 'N/A';

					if (($errorTrace['tempPath'] ?? null) === $file) {
						$file = $errorTrace['path'];
					}

					$errorLogsHtml .= <<<HTML
                    <hr />
                    <strong>Error Trace #{$i}:</strong><br>
                    Call: {$call}<br>
                    File: {$file}<br>
                    Line: {$line}<br>
                    HTML;
				}

				$uiError = <<<HTML
                <div style="padding:1rem; background:#fff3f3; border:1px solid #ffcccc; color:#a00; font-family:monospace;">
                    <strong>Main Error:</strong> {$errorTrace['message']}<br>
                    <strong>File:</strong> {$errorTrace['path']}<br>
                    <strong>Line:</strong> {$errorTrace['line']}<br>
                    {$errorLogsHtml}
                </div>
                HTML;

				$reported = true;
				throw new CompilerException($uiError);
			}
		}

		/**
		 * Compiles the content, checks for validity, and then captures its execution.
		 *
		 * This method first runs a "basic-only" compile for validation,
		 * followed by a full compile, capturing each phase safely.
		 *
		 * @param string $content The raw content to compile and execute.
		 * @param array $extract Variables to extract during rendering.
		 *
		 * @return string The fully rendered and compiled HTML/PHP output.
		 *
		 * @throws CompilerException|ReflectionException
		 */
		public static function compileAndCapture(string $content, array $extract = []): string {
			$capture = function($compiled, $extract) {
				ob_start();
				self::capture($compiled, $extract);
				return ob_get_clean();
			};

			$validate = self::compile($content, true);
			$captured = $capture($validate, $extract);
			$compiled = self::compile($captured);

			return $capture($compiled, $extract);
		}

		/**
		 * Creates a new Blade instance with the specified compiler.
		 *
		 * @param ViewsInterface $compiler The compiler instance to use.
		 */
		public function __construct(ViewsInterface $compiler) {
			$this->compiler = $compiler;
		}

		/**
		 * Registers a group of directives or configuration logic using a callback.
		 *
		 * @param Closure $callback A callback that receives the Blade instance.
		 * @return void
		 */
		public function register(Closure $callback): void {
			$callback($this);
			self::$instances[] = $this->compiler;
		}

		/**
		 * Registers a single custom directive for template compilation.
		 *
		 * @param string $directive The directive name (without the @ symbol).
		 * @param Closure $callback The callback defining the directive behavior.
		 * @param int $sequence Optional sequence priority for execution.
		 * @return void
		 */
		public function directive(string $directive, Closure $callback, int $sequence = 0): void {
			$this->compiler->directive($directive, $callback, $sequence);
		}

		/**
		 * Registers an advanced directive, typically used for nested or contextual logic.
		 *
		 * @param string $directive The directive name.
		 * @param Closure $callback The directive behavior handler.
		 * @param int $sequence Optional sequence number for ordering.
		 * @return void
		 */
		public function advanceDirective(string $directive, Closure $callback, int $sequence = 0): void {
			$this->compiler->advanceDirective($directive, $callback, $sequence);
		}

		/**
		 * Wraps a directive between a prefix and suffix.
		 *
		 * @param string $prefix The starting tag or wrapper.
		 * @param string $suffix The ending tag or wrapper.
		 * @param Closure $callback The callback defining the wrapping logic.
		 * @param bool $requireParams Whether the directive requires parameters.
		 * @return void
		 */
		public function wrap(string $prefix, string $suffix, Closure $callback, bool $requireParams = false): void {
			$this->compiler->wrap($prefix, $suffix, $callback, $requireParams);
		}

		/**
		 * Resolves the root path of the project.
		 * Automatically detects whether installed via Composer or not.
		 *
		 * @param string $path Optional path to append to the root.
		 * @return string The resolved project root path.
		 */
		public function getProjectRootPath(string $path = ''): string {
			if ($path) {
				$path = ltrim($path, '/');
			}

			$vendorPos = strpos(__DIR__, 'vendor');
			if ($vendorPos !== false) {
				return substr(__DIR__, 0, $vendorPos) . $path;
			}

			return dirname(__DIR__) . $path;
		}

		/**
		 * Builds a new Blade instance using the provided compiler.
		 *
		 * @param ViewsInterface $compiler The compiler implementation.
		 * @return self A new Blade instance.
		 */
		public static function build(ViewsInterface $compiler): self {
			return new self($compiler);
		}

		/**
		 * Loads all directive files from a given directory.
		 *
		 * @param string $path The absolute directory path containing directive files.
		 * @return void
		 *
		 * @throws CompilerException If the given path does not exist or is not a directory.
		 */
		public static function loadDirectives(string $path): void {
			if (!is_dir($path)) {
				throw new CompilerException("Directive path not found: {$path}");
			}

			foreach (glob("{$path}/__*.php") as $file) {
				require_once $file;
			}
		}

		/**
		 * Resolves a file path in a case-insensitive manner (useful on Linux systems).
		 *
		 * @param string $path The file or directory path to resolve.
		 * @return string|null The resolved absolute path or null if not found.
		 */
		private static function resolveCaseInsensitivePath(string $path): ?string {
			$parts = explode('/', trim($path, '/'));
			$current = '/';

			foreach ($parts as $part) {
				if (!is_dir($current) && !file_exists($current)) {
					return null;
				}

				$found = false;
				foreach (scandir($current) as $f) {
					if (strcasecmp($f, $part) === 0) {
						$current = rtrim($current, '/') . '/' . $f;
						$found = true;
						break;
					}
				}

				if (!$found) {
					return null;
				}
			}

			return $current;
		}

		/**
		 * Wraps the given content with open and close marker tags for tracking purposes.
		 *
		 * @param string $content The content to wrap.
		 * @return string The content wrapped with open and close markers.
		 */
		public static function parseWithMarker(string $content): string {
			return <<<PHP
            <?php /* open_tag marker */ ?>
            $content
            <?php /* close_tag marker */ ?>
            PHP;
		}
	}
