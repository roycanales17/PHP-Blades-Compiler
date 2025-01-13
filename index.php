<?php

	use App\Content\Blade;

	spl_autoload_register(function ($class) {
		$namespaces = [
			'App\\Content\\' => __DIR__ . '/src/'
		];
		foreach ($namespaces as $namespace => $baseDir) {
			if (str_starts_with($class, $namespace)) {
				$relativeClass = str_replace($namespace, '', $class);
				$file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

				if (file_exists($file)) {
					require_once $file;
				}
			}
		}
	});

	// Compile the content
	eval("?>". Blade::compile(file_get_contents('tests/home.php')));