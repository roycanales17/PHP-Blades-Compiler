<?php

	use App\Content\Blade;

	// Tags
	Blade::wrap("{{", "}}", function ($expression) {
		return "<?= htmlentities($expression ?? '') ?>";
	});

	Blade::wrap("{!!", "!!}", function ($expression) {
		return "<?= $expression ?? '' ?>";
	});