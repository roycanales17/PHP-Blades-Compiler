<?php

	use App\Content\Blade;

	Blade::wrap("{{--", "--}}", function ($expression) {
		return "<?php // $expression ?>";
	});

	Blade::wrap("{{", "}}", function ($expression) {
		return "<?= htmlentities($expression ?? '') ?>";
	});

	Blade::wrap("{!!", "!!}", function ($expression) {
		return "<?= $expression ?? '' ?>";
	});

	Blade::wrap('@php', '@endphp', function ($expression) {
		return "<?php $expression ?>";
	});