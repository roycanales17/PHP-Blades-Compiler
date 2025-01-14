<?php

	namespace App\Content;

	use ReflectionFunction;

	class Compile
	{
		private string $content;
		private array $blades = [];
		private array $tags = [];
		private string $root = '';
		private array $directives;
		private array $defaultDirectives = [
			'/directives/Tags.php',
			'/directives/Loops.php',
			'/directives/Template.php',
			'/directives/Conditional.php',
			'/directives/Variables.php'
		];

		function __construct(string $template, array $directives)
		{
			$this->content = $template;
			$this->directives = $directives;
		}

		protected function registerDirectives(array $blades): self
		{
			uksort($blades, function($key1, $key2) {
				return strlen($key2) - strlen($key1);
			});

			$this->blades = $blades;
			return $this;
		}

		protected function registerTags(array $tags): self
		{
			usort($tags, function ($a, $b) {
				return strlen($b['prefix']) - strlen($a['prefix']);
			});

			$this->tags = $tags;
			return $this;
		}

		public function importMainDirectory(string $root): self
		{
			$this->root = $root;
			return $this;
		}

		public function importDirectives(): void
		{
			foreach ($this->defaultDirectives as $directive) {
				require_once dirname(__DIR__) . $directive;
			}

			foreach ($this->directives as $directive) {
				require_once $this->root.'/'.ltrim($directive, '/');
			}

			$this->registerTags(Blade::getAllTags());
			$this->registerDirectives(Blade::getAllDirectives());
		}

		public function startCompile(): void
		{
			// Start with tags
			foreach ($this->tags as $tag) {
				$prefix = $tag['prefix'];
				$suffix = $tag['suffix'];
				$closure = $tag['callback'];
				$this->compileTags($prefix, $suffix, $closure);
			}

			// Next with directives
			foreach ($this->blades as $directive => $attr) {
				$closure = $attr['callback'];
				$replace = $attr['replace'];
				$this->compileTemplate($directive, $closure, $this->isRequireExpressions($closure), $replace);
			}
		}

		private function isRequireExpressions($closure): int
		{
			$reflection = new ReflectionFunction($closure);
			return $reflection->getNumberOfParameters();
		}

		private function compileTags(string $prefix, string $suffix, callable $template): void
		{
			$pattern = '/(?<!' . preg_quote($prefix, '/') . ')' . preg_quote($prefix, '/') . '\s*(.*?)\s*' . preg_quote($suffix, '/') . '(?!' . preg_quote($suffix, '/') . ')/';

			$this->content = preg_replace_callback($pattern, function ($matches) use ($template) {
				$expression = $matches[1];
				return $template($expression);
			}, $this->content);
		}


		private function compileTemplate(string $directive, $callback, int $params, bool $replace): void
		{
			if ($params)
				$pattern = '/@' . preg_quote($directive, '/') . '\s*\(([^)]*)\)/';
			else
				$pattern = '/@' . preg_quote($directive, '/') . '/';

			if ($replace) {
				preg_match($pattern, $this->content, $matches);
				if ($params) {
					$defaultParams = $this->defaultParams($params, $matches[1] ?? '');
					$this->content = $callback(...$defaultParams);
					return;
				}

				$this->content = $callback();
				return;
			}

			$this->content = preg_replace_callback($pattern, function ($matches) use ($callback, $params, $replace) {
				if ($params && $matches[1] ?? '') {
					$defaultParams = $this->defaultParams($params, $matches[1] ?? '') ?: [];
					return $callback(...$defaultParams);
				}

				return $callback();
			}, $this->content);
		}

		private function defaultParams(int $total, string $expression): array
		{
			$return = [];
			$params = [
				'expression' => $expression,
				'content' => $this->content
			];

			$index = 1;
			foreach ($params as $key => $value) {
				$return[$key] = $value;
				if ($index == $total)
					break;
				$index++;
			}

			return $return;
		}

		public function getTemplate(): string
		{
			return $this->content;
		}
	}