<?php

	namespace App\View\Compilers;

	use Throwable;

	final class Component
	{
		/**
		 * Parses custom <x-template> tags in HTML and replaces them with rendered views.
		 *
		 * @param  string  $content  HTML or Blade-like string
		 * @return string  Rendered output
		 */
		public static function renderComponents(string $content): string
		{
			// Match: <x-template ...> ... </x-template>
			$pattern = '/<x-template\s+([^>]+)>(.*?)<\/x-template>/is';

			return preg_replace_callback($pattern, function ($matches) {
				$rawAttributes = $matches[1] ?? '';
				$innerContent  = trim($matches[2] ?? '');

				$attributes = self::parseAttributes($rawAttributes);

				// Support required `src`
				if (empty($attributes['src'])) {
					return "<!-- ⚠️ Missing 'src' attribute in component -->";
				}

				// Render using your existing Blade-like view() helper
				$data = array_merge($attributes, [
					'content' => $innerContent,
				]);

				try {
					// Execute the component rendering immediately
					ob_start();
					Blade::load($attributes['src'], $data);
					return ob_get_clean();
				} catch (Throwable $e) {
					if (defined('DEVELOPMENT') && DEVELOPMENT) {
						return "<!-- ⚠️ Template render failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . " -->";
					}

					return "<!-- Template render failed. -->";
				}
			}, $content);
		}

		/**
		 * Converts raw attribute string into an associative array.
		 *
		 * Example: src="portfolio/utils/modal" identifier="loginModel"
		 *  → ['src' => 'portfolio/utils/modal', 'identifier' => 'loginModel']
		 */
		protected static function parseAttributes(string $raw): array
		{
			$attributes = [];
			$pattern = '/(\w+)\s*=\s*"([^"]*)"/';

			if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $attr) {
					$attributes[$attr[1]] = $attr[2];
				}
			}

			return $attributes;
		}
	}