<?php

	use App\Content\Blade;

	Blade::directive('template', function ($expression, $content) {
		if ($expression === '')
			return $content;

		$templatePath = preg_replace('/^["\']|["\']$/', '', trim($expression ,' '));
		$basePath = Blade::getProjectRootPath() . 'views/';
		$fullPath = $basePath . $templatePath;
		$candidatePaths = [];

		if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
			$candidatePaths[] = $fullPath;
		} else {
			$candidatePaths[] = $fullPath . '.blade.php';
			$candidatePaths[] = $fullPath . '.php';
		}

		$template = null;

		foreach ($candidatePaths as $path) {
			if (file_exists($path)) {
				$template = file_get_contents($path);
				break;
			}
		}

		if (!$template) {
			$trace = debug_backtrace();
			$info = $trace[4] ?? [];
			$resolved = 'Fail to resolved the path.';

			if (($info['function'] ?? '') == 'render')
				$resolved = $info['args'][0] ?? '';

			throw new Exception("
				<div style='font-family: sans-serif; background: #fdfdfd; border: 1px solid #ccc; padding: 20px; border-radius: 8px; color: #333;'>
					<h2 style='margin-top: 0; color: #d33;'>Blade Template Path Not Found</h2>
					<p>
						<strong>Template:</strong> <b style='color: #d33;'>@template($expression)</b><br/>
						<strong>Resolved Path:</strong> <b style='color: blue;'>{$resolved}</b>
					</p>
					<p><strong>Tried the following paths:</strong></p>
					<ul style='margin-top: 5px; padding-left: 20px; color: #555;'>
						" . implode('', array_map(fn($p) => "<li>$p</li>", $candidatePaths)) . "
					</ul>
				</div>
			");
		}

		$content = str_replace("@template($expression)", '', $content);
		return str_replace('@pageContent', $content, $template);
	}, true);


	Blade::directive('extends', function ($expression) {
		$expression = trim($expression);
		if ($expression === '')
			return '';

		$template = preg_replace('/^["\']|["\']$/', '', trim($expression ,' '));
		$basePath = Blade::getProjectRootPath() . 'views/';
		$fullPath = $basePath . $template;

		$candidatePaths = [];
		if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
			$candidatePaths[] = $fullPath;
		} else {
			$candidatePaths[] = $fullPath . '.blade.php';
			$candidatePaths[] = $fullPath . '.php';
		}

		foreach ($candidatePaths as $path) {
			if (file_exists($path)) {
				return Blade::compile(file_get_contents($path));
			}
		}

		$trace = debug_backtrace();
		$info = $trace[7] ?? [];
		$root = $info['file'] ?? '';

		if (($info['function'] ?? '') === '{closure}') {
			$info = $trace[6] ?? [];
			$argPath = $info['args'][0] ?? '';
			$root = $argPath ? $basePath . $argPath . '.php' : '';
		}

		throw new Exception("
			<div style='font-family: sans-serif; background: #fdfdfd; border: 1px solid #ccc; padding: 20px; border-radius: 8px; color: #333;'>
				<h2 style='margin-top: 0; color: #d33;'>Blade Extends Path Not Found</h2>
				<p>
					<strong>Template:</strong> <b style='color: #d33;'>@extends($expression)</b><br/>
					<strong>Resolved Path:</strong> <b style='color: blue;'>{$root}</b>
				</p>
				<p><strong>Tried the following paths:</strong></p>
				<ul style='margin-top: 5px; padding-left: 20px; color: #555;'>
					" . implode('', array_map(fn($p) => "<li>$p</li>", $candidatePaths)) . "
				</ul>
			</div>
		");
	});
