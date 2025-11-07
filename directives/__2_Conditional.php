<?php

	use App\View\Compilers\Blade;
	use App\View\Compilers\templates\Conditional;

	Blade::build(new Conditional)->register(function (Blade $blade)
	{
		$blade->directive('if', function ($expression) {
			return "<?php if($expression): ?>";
		});

		$blade->directive('elseif', function ($expression) {
			return "<?php elseif($expression): ?>";
		});

		$blade->directive('endif', function () {
			return "<?php endif; ?>";
		});

		$blade->directive('else', function () {
			return "<?php else: ?>";
		});

		// Add @switch, @case, @default, @endswitch
		$blade->directive('switch', function ($expression) {
			return "<?php switch($expression): ?>";
		});

		$blade->directive('case', function ($expression) {
			return "<?php case $expression: ?>";
		});

		$blade->directive('default', function () {
			return "<?php default: ?>";
		});

		$blade->directive('endswitch', function () {
			return "<?php endswitch; ?>";
		});

		// Add @isset and @endisset
		$blade->directive('isset', function ($expression) {
			return "<?php if(isset($expression)): ?>";
		});

		$blade->directive('endisset', function () {
			return "<?php endif; ?>";
		});

		// Add @empty and @endempty
		$blade->directive('empty', function ($expression) {
			return "<?php if(empty($expression)): ?>";
		});

		$blade->directive('endempty', function () {
			return "<?php endif; ?>";
		});

		// Add @unless and @endunless
		$blade->directive('unless', function ($expression) {
			return "<?php if(!$expression): ?>";
		});

		$blade->directive('endunless', function () {
			return "<?php endif; ?>";
		});

		// Add @error and @enderror
		$blade->directive('error', function ($expression) {
			return "<?php if(error($expression)): ?>";
		});

		$blade->directive('enderror', function () {
			return "<?php endif; ?>";
		});

		// Add @success and @endsuccess
		$blade->directive('success', function ($expression) {
			return "<?php if(success($expression)): ?>";
		});

		$blade->directive('endsuccess', function () {
			return "<?php endif; ?>";
		});
	});