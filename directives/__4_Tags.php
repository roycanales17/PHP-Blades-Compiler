<?php

	use App\Utilities\Session;
	use App\View\Compilers\Blade;
	use App\View\Compilers\templates\Tags;

	Blade::build(new Tags)->register(function (Blade $blade)
	{
		$blade->wrap("{{--", "--}}", function ($expression) {
			if (defined('DEVELOPMENT') && DEVELOPMENT) {
				return "<?php /* $expression */ ?>";
			}

			return "";
		});

		$blade->wrap("{{", "}}", function ($expression) {

			// Blade directives
			$remove = [
				'@php','@endphp',
				'@error','@enderror',
				'@section','@endsection',
				'@include',
				'@foreach','@endforeach',
				'@for','@endfor',
				'@while','@endwhile',
				'@break','@continue',
				'@do','@enddo',
				'@csrf','@json',
				'@yield','@extends',
				'@if','@elseif','@endif','@else',
				'@switch','@case','@default','@endswitch',
				'@isset','@endisset',
				'@empty','@endempty',
				'@unless','@endunless',
				'@success','@endsuccess',
				'@method','@post','@get','@server','@session',
				'@forelse','@endforelse',
				'@inject','@push','@stack','@prepend','@once','@endonce',
				'@verbatim','@endverbatim',
				'@php', '@endphp',
				'@includeIf','@includeWhen','@includeUnless'
			];

			// PHP tags that could break or inject code
			$phpTags = [
				'<?php', '?>',
				'<?=', '<?', '<%=', '<%',
			];

			// Template injection (avoid nested blade inside wrap)
			$templateSymbols = [
				'{{', '}}',
				'{!!', '!!}',
				'@{{', '}}',
			];

			// XSS-breaking characters
			$dangerousChars = [
				'<script', '</script>',
				'<style', '</style>',
				'<iframe', '</iframe>',
				'<object','</object>',
				'<embed','</embed>',
				'<svg','</svg>',
				'<img', '<video', '<audio',
				'onerror=', 'onclick=', 'onload=',
				'javascript:', 'vbscript:',
			];

			$removeAll = array_merge(
				$remove,
				$phpTags,
				$templateSymbols,
				$dangerousChars
			);

			// Remove everything dangerous
			$expression = str_ireplace($removeAll, '', $expression);
			return "<?= htmlentities($expression ?? '') ?>";
		});

		$blade->wrap("{!!", "!!}", function ($expression) {
			return "<?= $expression ?? '' ?>";
		});

		$blade->wrap('@php', '@endphp', function ($expression) {
			return "<?php $expression ?>";
		});

		$blade->wrap('@error', '@enderror', function ($expression, $key) {
			$key = str_replace(['"', "'"], '', $key);

			if (class_exists(Session::class) && function_exists('error')) {
				$message = error($key);

				// Insert again
				Session::flash("error:$key", $message);
				if ($message) {
					return $expression;
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