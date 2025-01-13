<?php

	use App\Content\Blade;

	// For loop
	Blade::directive('for', function ($expression) {
		return "<?php for($expression): ?>";
	});

	Blade::directive('endfor', function () {
		return "<?php endfor; ?>";
	});

	// Foreach loop
	Blade::directive('foreach', function ($expression) {
		return "<?php foreach($expression): ?>";
	});

	Blade::directive('endforeach', function () {
		return "<?php endforeach; ?>";
	});