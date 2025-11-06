<?php

	use App\View\Compilers\Blade;
	use App\View\Compilers\templates\Costume;

	Blade::build(new Costume)->register(function (Blade $blade)
	{
		$blade->directive('csrf', function () {
			$token = '';
			if (function_exists('csrf_token')) {
				$token = csrf_token();
			}
			return "<input type='hidden' name='csrf-token' value='$token'>";
		});

		$blade->directive('json', function ($expression) {
			return "<?= json_encode($expression) ?>";
		});

		$blade->directive('yield', function ($expression) use($blade) {
			$expression = trim($expression, '\'"');
			return $GLOBALS['__BLADE_YIELD__'][$expression] ?? '';
		}, 1);

		$blade->advanceDirective('include', function ($expression) use ($blade) {
			$orig_expression = $expression;
			$expression = trim($expression);
			if ($expression === '') return '';

			$expression = str_replace('.', '/', $expression);
			$template = preg_replace('/^["\']|["\']$/', '', trim($expression, ' '));

			if (function_exists('base_path')) {
				$basePath = base_path('/views/');
			} else {
				$basePath = $blade->getProjectRootPath('views/');
			}

			$fullPath = $basePath . $template;
			$candidatePaths = [];

			if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
				$candidatePaths[] = $fullPath;
			} else {
				$candidatePaths[] = $fullPath . '.blade.php';
				$candidatePaths[] = $fullPath . '.php';
				$candidatePaths[] = $fullPath . '.html';
			}

			foreach ($candidatePaths as $path) {
				if (file_exists($path)) {
					$compiled = Blade::load($path);
					return Blade::parseWithMarker($compiled);
				}
			}

			if (defined('DEVELOPMENT') && DEVELOPMENT) {
				return "Include path `$fullPath` is not exist. Original Path: $orig_expression";
			}

			return "";
		});

		$blade->advanceDirective('extends', function ($expression) use($blade) {
			$expression = trim($expression);
			if ($expression === '') return '';

			$orig_expression = $expression;
			$expression = ltrim($expression, '/');
			$expression = str_replace('.', '/', $expression);
			$templatePath = preg_replace('/^["\']|["\']$/', '', $expression);
			$basePath = $blade->getProjectRootPath('/views/');

			$candidatePaths = [];
			$fullPath = $basePath . $templatePath;

			if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
				$candidatePaths[] = $fullPath;
			} else {
				$candidatePaths[] = $fullPath . '.blade.php';
				$candidatePaths[] = $fullPath . '.php';
				$candidatePaths[] = $fullPath . '.html';
			}

			foreach ($candidatePaths as $path) {
				if (file_exists($path)) {
					return Blade::load($path);
				}
			}

			if (defined('DEVELOPMENT') && DEVELOPMENT) {
				return "<!-- Template path `$templatePath` is not exist. Original Path: $orig_expression -->`";
			}

			return "";
		}, 2);
	});