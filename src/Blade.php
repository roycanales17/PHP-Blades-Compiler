<?php

	namespace App\Content;

	use Closure;

	class Blade
	{
		use Properties;

		public static function directive(string $directive, Closure $callback, bool $replaceContent = false): void
		{
			self::register($directive, $callback, $replaceContent);
		}

		public static function wrap(string $prefix, string $suffix, Closure $callback): void
		{
			self::tag($prefix, $suffix, $callback);
		}

		public static function compile(string $template, array $directives = []): string
		{
			$compiler = new Compile($template, $directives);
			$compiler->importMainDirectory(dirname(getcwd()));
			$compiler->importDirectives();
			$compiler->startCompile();

			return $compiler->getTemplate();
		}

		public static function render(string $path, array $directives = []): void
		{
			if (file_exists($path = dirname(getcwd()).'/'.$path)) {
				$content = file_get_contents($path);
				eval("?>". self::compile($content, $directives));
			}
		}
	}