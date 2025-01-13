<?php

	use App\Content\Blade;

	// Break
	Blade::directive('break', function () {
		return '<?php break; ?>';
	});

	// Continue
	Blade::directive('continue', function () {
		return '<?php continue; ?>';
	});

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

	// While loop
	Blade::directive('while', function ($expression) {
		return "<?php while($expression): ?>";
	});

	Blade::directive('endwhile', function () {
		return "<?php endwhile; ?>";
	});

	// Do-while loop
	Blade::directive('do', function () {
		return "<?php do { ?>";
	});

	Blade::directive('enddo', function ($expression) {
		return "<?php } while($expression); ?>";
	});
