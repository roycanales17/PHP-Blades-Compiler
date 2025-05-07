<?php

	use App\Content\Blade;

	Blade::directive('template', function ($expression, $content) {
		if ($expression === '')
			return $content;

		$templatePath = preg_replace('/^["\']|["\']$/', '', trim($expression ,' '));
		$basePath = Blade::getProjectRootPath() . '/views/';
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

		if (!$template)
			throw new Exception("Blade template '{$template}' not found. Tried: " . implode(', ', $candidatePaths));

		$content = str_replace("@template($expression)", '', $content);
		return str_replace('@pageContent', $content, $template);
	}, true);


	Blade::directive('extends', function ($expression) {
		$expression = trim($expression);
		if ($expression === '')
			return '';

		$template = preg_replace('/^["\']|["\']$/', '', trim($expression ,' '));
		$basePath = Blade::getProjectRootPath() . '/views/';
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

		throw new Exception("Blade template '{$template}' not found. Tried: " . implode(', ', $candidatePaths));
	});
