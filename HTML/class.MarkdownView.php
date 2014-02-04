<?php

class MarkdownView extends View {

	function render() {
		$file = dirname($this->file) != '.'
			? $this->file
			: $this->folder.$this->file;
		$contents = file_get_contents($file);

		// with autoloader from composer this should not be necessary
		//include_once dirname(__FILE__) . '/../vendor/michelf/php-markdown/Michelf/Markdown.inc.php';
		if (class_exists('Markdown')) {
			$html = Markdown::defaultTransform($contents);
		}
		return $html;
	}

}
