<?php

	use App\Content\Blade;

	Blade::directive('post', function ($expression) {
		return "<?= \$_POST[$expression] ?? '' ?>";
	});

	Blade::directive('get', function ($expression) {
		return "<?= \$_GET[$expression] ?? '' ?>";
	});