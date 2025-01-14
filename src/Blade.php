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

		public static function render(string $path, array $directives = [], array $extract = []): void
		{
			if (file_exists($path = self::getProjectRootPath().'/'.$path)) {
				$content = file_get_contents($path);
				extract($extract);

				# Compile
				self::eval(self::compile($content, $directives));
			}
		}

		public static function eval(string $script): void
		{
			eval("?>$script");
		}

		public static function getProjectRootPath(): string
		{
			$vendorPos = strpos(__DIR__, 'vendor');
			if ($vendorPos !== false) {
				return substr(__DIR__, 0, $vendorPos);
			}

			return dirname(__DIR__);
		}
	}