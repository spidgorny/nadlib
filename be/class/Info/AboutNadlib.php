<?php

class AboutNadlib extends AppControllerBE
{

	var $file = 'index.md';

	function __construct()
	{
		parent::__construct();
		$this->file = $this->request->getNameless(1) ?: $this->file;
	}

	function render()
	{
		$v = new MarkdownView(AutoLoad::getInstance()->nadlibRoot . 'docs/' . $this->file);
		$content = $v->render();
		$content = preg_replace('/href="(.+\.md)"/', 'href="About/$1"', $content);
		return $content;
	}

}
