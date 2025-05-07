<?php

	namespace App\Content;

	use ReflectionFunction;

	class Compile
	{
		private string $content;
		private array $blades = [];
		private array $tags = [];
		private string $root = '';
		private static array $directives = [];
		private array $protectedRanges = [];
		private array $expressionCache = [];
		private array $defaultDirectives = [
			'/directives/Tags.php',
			'/directives/Loops.php',
			'/directives/Template.php',
			'/directives/Conditional.php',
			'/directives/Variables.php'
		];

		function __construct(string $content, array $directives)
		{
			$this->content = $content;

			if ($directives)
				self::$directives = array_merge(self::$directives, $directives);
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
			$this->root = rtrim($root, DIRECTORY_SEPARATOR);
			return $this;
		}

		public function importDirectives(): void
		{
			foreach ($this->defaultDirectives as $directive) {
				require_once dirname(__DIR__) . $directive;
			}

			foreach (self::$directives as $directive) {
				$path = $this->root . DIRECTORY_SEPARATOR . ltrim($directive, '/\\');
				require_once $path;
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
			if (isset($this->expressionCache[spl_object_hash($closure)])) {
				return $this->expressionCache[spl_object_hash($closure)];
			}

			$reflection = new ReflectionFunction($closure);
			$count = $reflection->getNumberOfParameters();
			$this->expressionCache[spl_object_hash($closure)] = $count;

			return $count;
		}

		private function compileTags(string $prefix, string $suffix, callable $template): void
		{
			$pattern = '/' . preg_quote($prefix, '/') . '\s*(.*?)\s*' . preg_quote($suffix, '/') . '/s';

			$offset = 0;
			$this->protectedRanges = [];

			$this->content = preg_replace_callback($pattern, function ($matches) use ($template, &$offset) {
				$fullMatch = $matches[0];
				$expression = $matches[1];

				$start = strpos($this->content, $fullMatch, $offset);
				if ($start === false) {
					return $fullMatch;
				}

				$end = $start + strlen($fullMatch);
				$offset = $end;

				if ($this->isInsideProtectedRange($start, $end)) {
					return $fullMatch;
				}

				$this->protectedRanges[] = [$start, $end];

				return $template($expression);
			}, $this->content);
		}

		private function isInsideProtectedRange(int $start, int $end): bool
		{
			foreach ($this->protectedRanges as [$rangeStart, $rangeEnd]) {
				if (
					($start >= $rangeStart && $start < $rangeEnd) ||
					($end > $rangeStart && $end <= $rangeEnd) ||
					($start <= $rangeStart && $end >= $rangeEnd)
				) {
					return true;
				}
			}
			return false;
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