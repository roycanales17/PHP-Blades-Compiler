<?php

	use App\Content\Blade;

	// Template
	Blade::directive('template', function ($expression, $content) {
		if (!trim($expression))
			return $content;

		$content = str_replace("@template($expression)", '', $content);
		$template = file_get_contents(getcwd(). '/' .str_replace(["'", '"', "(", ")"], "", $expression));
		return str_replace("@pageContent", $content, $template);
	}, true);