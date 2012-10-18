<?php

class Markdown extends View {

	function render() {
		$file = dirname($this->file) != '.'
			? $this->file
			: $this->folder.$this->file;
		$contents = file_get_contents($file);
		@include_once '../vendor/PHP Markdown 1.0.1o/markdown.php';
		if (function_exists('Markdown')) {
			$html = Markdown($contents);
		}
		return $html;
	}

}
