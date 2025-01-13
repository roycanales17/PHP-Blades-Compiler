<?php

	use App\Content\Blade;

	Blade::directive('template', function ($expression, $content) {
		if (!trim($expression))
			return $content;

		$content = str_replace("@template($expression)", '', $content);
		$template = file_get_contents(getcwd(). '/' .str_replace(["'", '"', "(", ")"], "", $expression));
		return str_replace("@pageContent", $content, $template);
	}, true);


	Blade::directive('extends', function ($expression, $content) {
		if (!trim($expression))
			return $content;

		$component = "";
		$path = getcwd(). '/'. str_replace(["'", '"', "(", ")"], "", $expression);

		if (file_exists($path))
			$component = file_get_contents($path);

		return $component;
	});