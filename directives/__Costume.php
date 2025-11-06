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

		$blade->directive('yield', function ($expression) {
			return $GLOBALS['__BLADE_YIELD__'][$expression] ?? '';
		}, 1);

		$blade->directive('include', function ($expression) use ($blade) {
			static $recentPath = [];

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
					$recentPath[] = $path;
					$content = file_get_contents($path);
					$rendered = $blade->render($content, $path);
					return <<<HTML
                    <?php /* open_tag marker */ ?>
                    $rendered
                    <?php /* close_tag marker */ ?>
                    HTML;
				}
			}

			throw new Exception("Template path `$fullPath` is not exist. Original Path: $orig_expression");
		});

		$blade->directive('extends', function ($expression) use($blade) {
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

			foreach ($candidatePaths as $templatePath) {
				if (file_exists($templatePath)) {
					$templateContent = file_get_contents($templatePath);
					return $blade->render($templateContent, $templatePath);
				}
			}

			throw new Exception("Template path `$fullPath` is not exist. Original Path: $orig_expression");
		}, 2);
	});