<?php

	namespace App\Content;

	use Closure;
	use Error;
	use Exception;

	class Blade
	{
		private static array $tracePaths = [];
		private static array $errorTraces = [];

		use Properties;

		public static function directive(string $directive, Closure $callback, bool $replaceContent = false): void
		{
			self::register($directive, $callback, $replaceContent);
		}

		public static function wrap(string $prefix, string $suffix, Closure $callback): void
		{
			self::tag($prefix, $suffix, $callback);
		}

		public static function compile(string $content, array $directives = []): string
		{
			$compiler = new Compile($content, $directives);
			$compiler->importMainDirectory(dirname(getcwd()));
			$compiler->importDirectives();
			$compiler->startCompile();

			return $compiler->getTemplate();
		}

		public static function render(string $path, array $directives = [], array $extract = []): void
		{
			self::$tracePaths[] = $path;

			if (file_exists($path = Helper::getProjectRootPath().'/'.$path)) {

				# Fetch the content
				$content = file_get_contents($path);

				# Compile
				self::eval(self::compile($content, $directives), $extract);
			}
		}

		private static function eval(string $script, array $data = []): void
		{
			$development = defined('DEVELOPMENT') && DEVELOPMENT;
			$tempFile = $development ? '../compiled.php' : tempnam(sys_get_temp_dir(), 'tpl_') . '.php';
			file_put_contents($tempFile, $script);

			try {
				$__resolvedPath = '';
				(static function () use ($tempFile, $data, &$__resolvedPath) {
					$__resolvedPath = self::$tracePaths[count(self::$tracePaths) - 1];
					extract($data, EXTR_SKIP);
					include $tempFile;
				})();
			} catch (Exception|Error $e) {
				if (empty(self::$errorTraces)) {
					self::$errorTraces = [
						'message' => $e->getMessage(),
						'line' => $e->getLine(),
						'path' => $__resolvedPath,
						'code' => (int) $e->getCode(),
						'traces' => $e->getTrace()
					];
				}
			} finally {
				if (!$development) {
					unlink($tempFile);
				}
			}

			if (!empty(self::$errorTraces)) {
				$errorTrace = self::$errorTraces;

				// Build HTML for errorLogs
				$errorLogsHtml = '';
				foreach ($errorTrace['traces'] as $i => $log) {
					$func = $log['function'];
					$file = $log['file'] ?? 'N/A';
					$line = $log['line'] ?? 'N/A';

					if (realpath($tempFile) == $file)
						$file = $errorTrace['path'];

					$errorLogsHtml .= <<<HTML
					<hr>
					<strong>Error Trace #{$i}:</strong><br>
					Message: $func()<br>
					File: $file<br>
					Line: $line<br>
					HTML;
				}


				// Final UI output
				$uiError = <<<HTML
				<div style="padding:1rem; background:#fff3f3; border:1px solid #ffcccc; color:#a00; font-family:monospace;">
					<strong>Main Error:</strong> {$errorTrace['message']}<br>
					<strong>File:</strong> {$errorTrace['path']}<br>
					<strong>Line:</strong> {$errorTrace['line']}<br>
					{$errorLogsHtml}
				</div>
				HTML;

				throw new Exception($uiError);
			}
		}
	}