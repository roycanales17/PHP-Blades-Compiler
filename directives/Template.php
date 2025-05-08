<?php

	use App\Content\Blade;

	Blade::directive('template', function ($expression, $content) {
		if ($expression === '')
			return $content;

		$templatePath = preg_replace('/^["\']|["\']$/', '', trim($expression ,' '));
		$basePath = rtrim(Blade::getProjectRootPath(), '/') . '/views/';
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
			Blade::resolveError(debug_backtrace(), [
				'expression' => $expression,
				'candidatePaths' => $candidatePaths,
				'template' => 'template'
			]);
		}

		$content = str_replace("@template($expression)", '', $content);
		return str_replace('@pageContent', $content, $template);
	}, true);


	Blade::directive('extends', function ($expression) {
		static $recentPath = [];

		$expression = trim($expression);
		if ($expression === '')
			return '';

		$template = preg_replace('/^["\']|["\']$/', '', trim($expression ,' '));
		$basePath = rtrim(Blade::getProjectRootPath(), '/') . '/views/';
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
				$recentPath[] = $path;
				return Blade::compile(file_get_contents($path));
			}
		}

		$resolvedPath = '';
		if ($recentPath) {
			$resolvedPath = $recentPath[count($recentPath) - 1];
		}

		Blade::resolveError(debug_backtrace(), [
			'expression' => $expression,
			'candidatePaths' => $candidatePaths,
			'resolvedPath' => $resolvedPath,
			'template' => 'extends'
		]);
	});
