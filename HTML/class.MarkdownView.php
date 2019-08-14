<?php

class MarkdownView extends View
{

	function render()
	{
		$file = dirname($this->file) != '.'
			? $this->file
			: $this->folder . $this->file;
		$contents = file_get_contents($file);

		// with autoloader from composer this should not be necessary
		//include_once dirname(__FILE__) . '/../vendor/michelf/php-markdown/Michelf/Markdown.inc.php';
		if (class_exists('\Michelf\Markdown')) {
			$html = \Michelf\Markdown::defaultTransform($contents);
		} else {
			$this->index->error('Markdown class in not installed');
			$html = $contents;
		}
		return $html;
	}

}
