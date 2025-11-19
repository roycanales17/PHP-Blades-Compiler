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
		});

		$blade->advanceDirective('include', function ($expression) use ($blade) {
			$orig_expression = $expression;
			$expression = trim($expression);
			if ($expression === '') return '';

			if (strpos($expression, ',') !== false) {
				preg_match("/^'([^']+)'\s*,\s*(.+)$/s", $expression, $matches);
				$param1 = $matches[1];
				$param2 = trim($matches[2]);
			} else {
				$param1 = trim($expression, "'\"");
				$param2 = null;
			}

			$param2Array = [];
			if ($param2 !== null) {
				if (str_starts_with($param2, '[') && str_ends_with($param2, ']')) {
					preg_match_all('/\$(\w+)/', $param2, $varMatches);
					$variables = $varMatches[1] ?? [];

					$replacements = [];
					foreach ($variables as $var) {
						if (isset($GLOBALS['__BLADES_VARIABLES_2__'][$var])) {
							$replacements['$'.$var] = var_export($GLOBALS['__BLADES_VARIABLES_2__'][$var], true);
						} elseif (isset($GLOBALS['__BLADES_VARIABLES__'][$var])) {
							$replacements['$'.$var] = var_export($GLOBALS['__BLADES_VARIABLES__'][$var], true);
						} else {
							$replacements['$'.$var] = 'null';
						}
					}

					$param2Eval = strtr($param2, $replacements);
					$tempFile = sys_get_temp_dir() . '/blade_param_' . uniqid() . '.php';
					file_put_contents($tempFile, "<?php\nreturn $param2Eval;\n");

					$param2Array = include $tempFile;
					unlink($tempFile);

				} else {
					// Single variable or static string
					if (str_starts_with($param2, '$')) {
						$varName = substr($param2, 1);

						if (isset($GLOBALS['__BLADES_VARIABLES_2__'][$varName])) {
							$param2Array = $GLOBALS['__BLADES_VARIABLES_2__'][$varName];
						} elseif (isset($GLOBALS['__BLADES_VARIABLES__'][$varName])) {
							$param2Array = $GLOBALS['__BLADES_VARIABLES__'][$varName];
						}
					} else {
						$param2Array = ['value' => trim($param2, "'\"")];
					}
				}
			}

			$template = str_replace('.', '/', $param1);
			if (function_exists('base_path')) {
				$basePath = base_path('/views/');
			} else {
				$basePath = $blade->getProjectRootPath('views/');
			}

			$fullPath = $basePath . $template;
			if (!pathinfo($fullPath, PATHINFO_EXTENSION)) {
				$fullPath .= '.blade.php';
			}

			if (file_exists($fullPath)) {
				$compiled = Blade::load($fullPath, $param2Array);
				return Blade::parseWithMarker($compiled);
			}

			if (defined('DEVELOPMENT') && DEVELOPMENT) {
				return "Include path `$fullPath` does not exist. Original Path: $orig_expression";
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

			$fullPath = $basePath . $templatePath;
			if (!pathinfo($fullPath, PATHINFO_EXTENSION)) {
				$fullPath = $fullPath . '.blade.php';
			}

			if (file_exists($fullPath)) {
				return Blade::load($fullPath);
			}

			if (defined('DEVELOPMENT') && DEVELOPMENT) {
				return "<!-- Template path `$templatePath` is not exist. Original Path: $orig_expression -->`";
			}

			return "";
		});
	});