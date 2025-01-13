<?php

	namespace App\Content;

	use Closure;

	trait Properties
	{
		private static array $blades = [];
		private static array $tags = [];

		protected static function register(string $directive, Closure $callback, bool $replace = false): void
		{
			self::$blades[$directive] = [
				'callback' => $callback,
				'replace' => $replace
			];
		}

		protected static function tag(string $prefix, string $suffix, Closure $callback): void
		{
			self::$tags[] = [
				'prefix' => $prefix,
				'suffix' => $suffix,
				'callback' => $callback
			];
		}

		protected static function getDirective(string $directive):? Closure
		{
			if (isset(self::$blades[$directive])) {
				return self::$blades[$directive];
			}

			return null;
		}

		public static function getAllTags(): array
		{
			return self::$tags;
		}

		public static function getAllDirectives(): array
		{
			return self::$blades;
		}
	}