<?php

	namespace App\View\Compilers;

	final class Buffer extends Compiler
	{
		public function registerWrap(array $wrapper): void {
			$this->wrapper = array_merge($this->wrapper, $wrapper);
		}

		public function registerDirectives(array $directives): void {
			$this->directives = array_merge($this->directives, $directives);
		}

		public function registerSequences(array $sequences): void {
			$this->sequence = array_merge($this->sequence, $sequences);
		}
	}