<?php

	namespace App\View\Compilers;

	use Throwable;
	use Error;

	/**
	 * Class Component
	 *
	 * A lightweight Blade-like component compiler that processes custom <x-*> tags in HTML templates.
	 * It allows developers to define reusable view components similar to Laravel Blade components.
	 *
	 * Features:
	 * - Supports both standard (<x-card>...</x-card>) and self-closing (<x-icon ... />) components
	 * - Allows nested components (components within components)
	 * - Converts component tag names to their corresponding .blade.php file paths
	 * - Automatically passes attributes and slot content to the rendered component
	 * - Supports error-safe rendering and optional development debugging
	 *
	 * Example:
	 * ```html
	 * <x-card title="Welcome">
	 *     <x-button text="Click Me" />
	 * </x-card>
	 * ```
	 *
	 * This will look for:
	 * - views/card.blade.php
	 * - views/button.blade.php
	 *
	 * and render them recursively with proper attribute and slot handling.
	 */
	final class Component
	{
		/**
		 * Parses and renders all custom <x-*> components within the given HTML or Blade-like string.
		 *
		 * This function:
		 * 1. Detects both self-closing and paired <x-*> tags.
		 * 2. Recursively renders nested components.
		 * 3. Converts component names (like `x-utils.modal`) to proper view file paths (`views/utils/modal.blade.php`).
		 * 4. Loads and renders the component using the Blade::load() engine.
		 *
		 * @param  string  $content
		 *         The raw HTML or Blade content containing <x-*> component tags.
		 *
		 * @return string
		 *         The fully rendered HTML with all components replaced by their rendered views.
		 */
		public static function renderComponents(string $content): string
		{
			// Match both: <x-name>...</x-name> and self-closing <x-name ... />
			$pattern = '/<x-([\w\.\-\/]+)([^>]*)>(.*?)<\/x-\1>|<x-([\w\.\-\/]+)([^>]*)\/>/is';

			// Repeat until no more components are found (to handle nested components)
			while (preg_match($pattern, $content)) {
				$content = preg_replace_callback($pattern, function ($matches) {
					// Detect component type (normal vs self-closing)
					$component = $matches[1] ?: $matches[4];
					$rawAttributes = $matches[2] ?: $matches[5];
					$inner = $matches[3] ?? '';

					// Parse attributes into associative array
					$attributes = self::parseAttributes(trim($rawAttributes));

					// Convert dotted or slashed component names to file paths
					$path = str_replace(['.', '\\'], '/', $component);
					if (function_exists('base_path')) {
						$file = base_path("/views/{$path}.blade.php");
					} else {
						$file = "../views/{$path}.blade.php";
					}

					// If component file does not exist, show debug comment (if DEVELOPMENT)
					if (!file_exists($file)) {
						if (defined('DEVELOPMENT') && DEVELOPMENT) {
							return "<!-- ⚠️ X-Component not found: {$component} -->";
						}
						return "";
					}

					// Recursively render nested components inside the inner content
					if (strpos($inner, '<x-') !== false) {
						$inner = self::renderComponents($inner);
					}

					// Capture and render the component output
					return Blade::load($file, array_merge($attributes, ['slot' => $inner]));
				}, $content);
			}

			return $content;
		}

		/**
		 * Converts a raw HTML attribute string into an associative PHP array.
		 *
		 * Example:
		 * ```php
		 * parseAttributes('title="Welcome" id="main" visible="true"');
		 * // Returns: ['title' => 'Welcome', 'id' => 'main', 'visible' => 'true']
		 * ```
		 *
		 * Includes a simple static cache for repeated attributes to improve performance.
		 *
		 * @param  string  $raw
		 *         The raw attribute string (e.g., `title="Welcome" id="main"`).
		 *
		 * @return array<string, string>
		 *         An associative array of attribute key-value pairs.
		 */
		protected static function parseAttributes(string $raw): array
		{
			static $cache = []; // Cache identical attribute sets

			if (isset($cache[$raw])) {
				return $cache[$raw];
			}

			$attributes = [];
			$pattern = '/(\w+)\s*=\s*"([^"]*)"/';

			if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $attr) {
					$attributes[$attr[1]] = $attr[2];
				}
			}

			// Store result in cache for reuse
			return $cache[$raw] = $attributes;
		}
	}