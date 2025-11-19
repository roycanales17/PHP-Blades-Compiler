<?php

	namespace App\View\Compilers;

	/**
	 * Class Component
	 *
	 * A lightweight Blade-like component compiler that processes custom <x-*> tags in HTML templates.
	 *
	 * Features:
	 * - Supports standard (<x-card>...</x-card>) and self-closing (<x-icon ... />) components.
	 * - Allows nested components (components inside components).
	 * - Converts component tag names (like `x-utils.modal`) to corresponding Blade view paths (`views/utils/modal.blade.php`).
	 * - Automatically passes parsed attributes and slot content to the component.
	 * - Resolves dynamic attributes (prefixed with `:`) from `$GLOBALS['__BLADES_VARIABLES_2__']` or `$GLOBALS['__BLADES_VARIABLES__']`.
	 * - Supports optional development debugging if a component file is missing.
	 *
	 * Example usage in a Blade-like template:
	 * ```html
	 * <x-card title="Welcome">
	 *     <x-button :options="$buttonOptions" selected="primary" />
	 * </x-card>
	 * ```
	 */
	final class Component
	{
		/**
		 * Parses and renders all custom <x-*> components within the given HTML content.
		 *
		 * This method:
		 * 1. Detects both self-closing and paired <x-*> tags.
		 * 2. Recursively renders nested components.
		 * 3. Converts component names (e.g., `x-utils.modal`) to view file paths.
		 * 4. Loads and renders the component using `Blade::load()`.
		 *
		 * @param string $content The raw HTML or Blade-like content containing <x-*> tags.
		 * @return string The fully rendered HTML with all components replaced by their rendered views.
		 */
		public static function renderComponents(string $content): string
		{
			// Regex to match both standard and self-closing component tags
			$pattern = '/<x-([\w\.\-\/]+)([^>]*)>(.*?)<\/x-\1>|<x-([\w\.\-\/]+)([^>]*)\/>/is';

			// Repeat until no more components are found (handles nested components)
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
					$file = function_exists('base_path')
						? base_path("/views/{$path}.blade.php")
						: "../views/{$path}.blade.php";

					// If a component file does not exist, optionally output debug comment
					if (!file_exists($file)) {
						if (defined('DEVELOPMENT') && DEVELOPMENT) {
							return "<!-- ⚠️ X-Component not found: {$component} -->";
						}
						return "";
					}

					// Recursively render nested components in inner content
					if (strpos($inner, '<x-') !== false) {
						$inner = self::renderComponents($inner);
					}

					// Render component with attributes and slot
					return Blade::load($file, array_merge($attributes, ['slot' => $inner]));
				}, $content);
			}

			return $content;
		}

		/**
		 * Converts a raw HTML or Blade attribute string into an associative PHP array.
		 *
		 * Example:
		 * ```php
		 * $raw = 'label="Type"	:options="$transactionTypeOptions" selected="expense"';
		 * $parsed = Component::parseAttributes($raw);
		 * // Returns:
		 * // [
		 * //	'label' => 'Type',
		 * //	'options' => ['expense','income'], // resolved from globals
		 * //	'selected' => 'expense'
		 * // ]
		 * ```
		 *
		 * @param string $raw The raw attribute string (e.g., `title="Welcome" :options="$foo"`).
		 * @return array<string, mixed> Associative array of parsed attributes.
		 */
		protected static function parseAttributes(string $raw): array
		{
			static $cache = [];

			if (isset($cache[$raw])) {
				return $cache[$raw];
			}

			$attributes = [];

			// Match key="value", key='value', :key="value", :key='value', :key=$var
			preg_match_all('/(:?[\w\-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/', $raw, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr) {
				$key = $attr[1];
				$value = $attr[2] ?? $attr[3] ?? $attr[4] ?? '';

				if (str_starts_with($key, ':')) {
					$key = substr($key, 1);

					// Remove leading $ if exists
					if (str_starts_with($value, '$')) {
						$value = substr($value, 1);
					}

					// Resolve dynamic value from globals
					$resolved = $GLOBALS['__BLADES_VARIABLES_2__'][$value] ??
						$GLOBALS['__BLADES_VARIABLES__'][$value] ??
						null;

					$attributes[$key] = $resolved !== null ? $resolved : $value;
				} else {
					$attributes[$key] = $value;
				}
			}

			return $cache[$raw] = $attributes;
		}
	}
