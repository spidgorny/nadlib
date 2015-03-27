<?php

class MarkdownView extends View {

	/**
	 * Cached processed markdown to HTML
	 * @var string
	 */
	var $content;

	function render() {
		if ($this->content) {
			return $this->content;
		}
		$file = dirname($this->file) != '.'
			? $this->file
			: $this->folder.$this->file;
		$contents = file_get_contents($file);

		// with autoloader from composer this should not be necessary
		//include_once dirname(__FILE__) . '/../vendor/michelf/php-markdown/Michelf/Markdown.inc.php';
		if (class_exists('\Michelf\Markdown')) {
			$html = Michelf\Markdown::defaultTransform($contents);
		} else {
			$this->index->error('Markdown class in not installed');
			$html = $contents;
		}
		return $html;
	}

	public function processIncludes() {
		$content = $this->render();
		$content = preg_replace_callback('/{{(.+?)}}/', function ($matches) {
			return new MarkdownView($matches[1]);
		}, $content);
		$this->content = $content;
	}

}
