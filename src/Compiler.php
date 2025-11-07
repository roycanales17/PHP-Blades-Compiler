<?php

	namespace App\View\Compilers;

	use App\View\Compilers\scheme\CompilerException;
	use App\View\Compilers\scheme\ViewsInterface;
	use App\View\Compilers\Builder\Directive;
	use App\View\Compilers\Builder\Wrapper;
	use ReflectionException;
	use Closure;

	/**
	 * Abstract Compiler class providing the base functionality for compiling views with custom directives and wrappers.
	 */
	abstract class Compiler implements ViewsInterface
	{
		/**
		 * The current content being compiled.
		 *
		 * @var string
		 */
		private string $content = '';

		/**
		 * List of wrapper definitions (prefix, suffix, callback, etc.).
		 *
		 * @var array
		 */
		protected array $wrapper = [];

		/**
		 * List of standard directives and their callbacks.
		 *
		 * @var array
		 */
		protected array $directives = [];

		/**
		 * List of advanced directives that execute after standard ones.
		 *
		 * @var array
		 */
		protected array $advanceDirectives = [];

		/**
		 * Sequence of directives with ordered execution.
		 *
		 * @var array
		 */
		protected array $sequence = [];

		/**
		 * Registers a new directive with its corresponding callback.
		 *
		 * @param string $directive
		 * @param Closure $callback
		 * @return void
		 * @throws CompilerException If the directive name is empty.
		 */
		public function directive(string $directive, Closure $callback): void {
			if (!$directive) {
				throw new CompilerException("Directive is required");
			}

			$this->directives[$directive] = $callback;
		}

		/**
		 * Registers an advanced directive that should be compiled after all standard directives.
		 *
		 * @param string $directive Name of the directive.
		 * @param Closure $callback Directive callback function.
		 * @return void
		 * @throws CompilerException If the directive name is empty.
		 */
		public function advanceDirective(string $directive, Closure $callback): void {
			if (!$directive) {
				throw new CompilerException("Directive is required");
			}

			$this->advanceDirectives[$directive] = $callback;
		}

		/**
		 * Registers a pair of wrapping tags and their callback.
		 *
		 * @param string $prefix Opening tag/pattern.
		 * @param string $suffix Closing tag/pattern.
		 * @param Closure $callback Callback to handle the wrap content.
		 * @param bool $requireParams Whether the wrap requires parameters.
		 * @return void
		 * @throws CompilerException If prefix or suffix is missing.
		 */
		public function wrap(string $prefix, string $suffix, Closure $callback, bool $requireParams = false): void {
			if (!$prefix || !$suffix) {
				throw CompilerException::invalidWrapper('both prefix and suffix.');
			}

			$this->wrapper[] = [
				'prefix' => $prefix,
				'suffix' => $suffix,
				'callback' => $callback,
				'require_params' => $requireParams
			];
		}

		/**
		 * Applies all registered wrapper directives to the content.
		 *
		 * @return void
		 * @throws CompilerException
		 */
		private function compileWrapper(): void {
			foreach ($this->wrapper as $tag) {
				$wrapper = new Wrapper($this->content);
				$wrapper->prefix($tag['prefix']);
				$wrapper->suffix($tag['suffix']);
				$wrapper->callback($tag['callback']);
				$this->content = $wrapper->build($tag['require_params']);
			}
		}

		/**
		 * Applies all registered standard directives to the content.
		 *
		 * @return void
		 * @throws CompilerException|ReflectionException
		 */
		private function compileStandards(): void {
			foreach ($this->directives as $directive => $callback) {
				$directiveHandler = new Directive($this->content);
				$directiveHandler->directive($directive);
				$directiveHandler->callback($callback);
				$this->content = $directiveHandler->build();
			}
		}

		/**
		 * Applies all registered advanced directives to the content.
		 *
		 * These are compiled after standard and sequence directives.
		 *
		 * @return void
		 * @throws CompilerException|ReflectionException
		 */
		private function compileAdvanceStandards(): void
		{
			foreach ($this->advanceDirectives as $directive => $callback) {
				$directiveHandler = new Directive($this->content);
				$directiveHandler->directive($directive);
				$directiveHandler->callback($callback);
				$this->content = $directiveHandler->build();
			}
		}

		/**
		 * Compiles the given content by applying all wrappers and directives.
		 *
		 * @deprecated Do not use this function,
		 * @param string $content Raw content to compile.
		 * @param bool $basicOnly If true, skips compiling advanced directives.
		 * @return string Compiled content.
		 * @throws CompilerException|ReflectionException
		 */
		public function compile(string $content, bool $basicOnly = false): string {
			$this->content = $content;

			// Sorting
			$this->sequence = array_values($this->sequence);

			// prioritizing longer strings first.
			usort($this->wrapper, fn($a, $b) => strlen($b['prefix']) - strlen($a['prefix']));
			uksort($this->directives, fn($k1, $k2) => strlen($k2) - strlen($k1));

			// Start Compile
			if (method_exists($this, 'build')) {
				$this->build();
			} else {
				$this->compileWrapper();
				$this->compileStandards();
				if (!$basicOnly) {
					$this->compileAdvanceStandards();
				}
			}

			// Return the result
			return $this->content;
		}

		/**
		 * Updates the internal content state.
		 *
		 * This method allows subclasses to modify the current content manually.
		 *
		 * @param string $content The new content to set.
		 * @return void
		 */
		protected function updateContent(string $content): void {
			$this->content = $content;
		}

		/**
		 * Retrieves the current content.
		 *
		 * This can be useful for subclasses that need to inspect or modify the content directly.
		 *
		 * @return string The current compiled content.
		 */
		protected function getContent(): string {
			return $this->content;
		}

		/**
		 * Retrieves all registered wrappers.
		 *
		 * Each wrapper consists of a prefix, suffix, and associated callback.
		 *
		 * @return array The list of registered wrappers.
		 */
		public function getWrapper(): array {
			return $this->wrapper;
		}

		/**
		 * Retrieves all registered standard directives.
		 *
		 * Each directive is an entry where the key is the directive name
		 * and the value is its associated callback.
		 *
		 * @return array The list of registered directives.
		 */
		public function getDirectives(): array {
			return $this->directives;
		}

		/**
		 * Retrieves all registered advanced directives.
		 *
		 * @return array The list of advanced directives with their callbacks.
		 */
		public function getAdvanceDirectives(): array {
			return $this->advanceDirectives;
		}
	}
