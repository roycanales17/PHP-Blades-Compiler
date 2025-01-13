<?php

	use App\Content\Blade;

	Blade::directive('if', function ($expression) {
		return "<?php if($expression): ?>";
	});

	Blade::directive('elseif', function ($expression) {
		return "<?php elseif($expression): ?>";
	});

	Blade::directive('endif', function () {
		return "<?php endif; ?>";
	});

	Blade::directive('else', function () {
		return "<?php else: ?>";
	});

	// Add @switch, @case, @default, @endswitch
	Blade::directive('switch', function ($expression) {
		return "<?php switch($expression): ?>";
	});

	Blade::directive('case', function ($expression) {
		return "<?php case $expression: ?>";
	});

	Blade::directive('default', function () {
		return "<?php default: ?>";
	});

	Blade::directive('endswitch', function () {
		return "<?php endswitch; ?>";
	});

	// Add @isset and @endisset
	Blade::directive('isset', function ($expression) {
		return "<?php if(isset($expression)): ?>";
	});

	Blade::directive('endisset', function () {
		return "<?php endif; ?>";
	});

	// Add @empty and @endempty
	Blade::directive('empty', function ($expression) {
		return "<?php if(empty($expression)): ?>";
	});

	Blade::directive('endempty', function () {
		return "<?php endif; ?>";
	});

	// Add @unless and @endunless
	Blade::directive('unless', function ($expression) {
		return "<?php if(!$expression): ?>";
	});

	Blade::directive('endunless', function () {
		return "<?php endif; ?>";
	});