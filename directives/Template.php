<?php

	use App\Content\Blade;

	Blade::directive('template', function ($expression, $content) {
		if (!trim($expression))
			return $content;

		$path = Blade::getProjectRootPath(). '/' .str_replace(["'", '"', "(", ")"], "", $expression);

		if (!file_exists($path))
			return $content;

		$content = str_replace("@template($expression)", '', $content);
		$template = file_get_contents($path);
		return str_replace("@pageContent", $content, $template);
	}, true);


	Blade::directive('extends', function ($expression) {
		if (!trim($expression))
			return "";

		$component = "";
		$path = Blade::getProjectRootPath(). '/'. str_replace(["'", '"', "(", ")"], "", $expression);

		if (file_exists($path))
			$component = file_get_contents($path);

		return $component;
	});