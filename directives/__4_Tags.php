<?php

	use App\Utilities\Session;
	use App\View\Compilers\Blade;
	use App\View\Compilers\templates\Tags;

	Blade::build(new Tags)->register(function (Blade $blade)
	{
		$blade->wrap("{{--", "--}}", function ($expression) {
			return "<?php /* $expression */ ?>";
		});

		$blade->wrap("{{", "}}", function ($expression) {
			return "<?= htmlentities($expression ?? '') ?>";
		});

		$blade->wrap("{!!", "!!}", function ($expression) {
			return "<?= $expression ?? '' ?>";
		});

		$blade->wrap('@php', '@endphp', function ($expression) {
			return "<?php $expression ?>";
		});

		$blade->wrap('@error', '@enderror', function ($expression, $key) {
			if (class_exists(Session::class)) {
				$key = str_replace(['"', "'"], '', $key);
				$message = Session::flash($key);

				// Insert again
				Session::flash("error:$key", $message);
				if ($message) {
					return "<?php if (true): ?>
								$expression
							<?php endif; ?>";
				}
			} else {
				// Todo: Will add the legacy logic here...
			}

			return "";
		}, true);

		$blade->wrap('@section', '@endsection', function ($expression, $param) use($blade) {
			if (!isset($GLOBALS['__BLADE_YIELD__'])) {
				$GLOBALS['__BLADE_YIELD__'] = [];
			}

			$content = Blade::compileAndCapture($expression);
			$GLOBALS['__BLADE_YIELD__'][$param] = Blade::parseWithMarker($content);

			return "";
		}, true);
	});