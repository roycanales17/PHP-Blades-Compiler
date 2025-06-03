<?php

	namespace App\Content;

	use Exception;

	class Helper
	{
		public static function getProjectRootPath(): string
		{
			$vendorPos = strpos(__DIR__, 'vendor');
			if ($vendorPos !== false) {
				return substr(__DIR__, 0, $vendorPos);
			}

			return dirname(__DIR__);
		}

		public static function resolveError(array $traces, array $attr): void
		{
			$stop = false;
			$expression = $attr['expression'] ?? '';
			$candidatePaths = $attr['candidatePaths'] ?? [];
			$resolvedPath = $attr['resolvedPath'] ?? '';
			$template = $attr['template'] ?? '';

			if (!$resolvedPath) {
				foreach ($traces as $trace) {
					$file = $trace['file'] ?? '';
					$file = explode(DIRECTORY_SEPARATOR, $file);
					$file = array_pop($file);

					if ($stop) {
						$resolvedPath = $trace['args'][0] ?? '';
						break;
					}

					if (in_array($file, ['Blade.php', 'Component.php']) && ($trace['function'] ?? '') == 'compile')
						$stop = true;
				}
			}

			$title = ucfirst($template);
			throw new Exception("
				<div style='font-family: sans-serif; background: #fdfdfd; border: 1px solid #ccc; padding: 20px; border-radius: 8px; color: #333;'>
					<h2 style='margin-top: 0; color: #d33;'>Blade $title Path Not Found</h2>
					<p>
						<strong>Template:</strong> <b style='color: #d33;'>@$template($expression)</b><br/>
						<strong>Resolved Path:</strong> <b style='color: blue;'>{$resolvedPath}</b>
					</p>
					<p><strong>Tried the following paths:</strong></p>
					<ul style='margin-top: 5px; padding-left: 20px; color: #555;'>
						" . implode('', array_map(fn($p) => "<li>$p</li>", $candidatePaths)) . "
					</ul>
				</div>
			");
		}
	}