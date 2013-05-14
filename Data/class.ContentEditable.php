<?php

class ContentEditable {

	/**
	 * @var string EventVersionsAvailable
	 */
	protected $file;

	/**
	 * @var string FAQ/EventVersionsAvailable.txt
	 */
	protected $filename;

	/**
	 * @var string content of the file
	 */
	public $content;

	function __construct($file) {
		$this->file = $file;
		$this->filename = 'pages/'.$this->file.'.txt';
		$this->content = @file_get_contents($this->filename);
		if (!$this->content) {
			$this->content = '&nbsp;';
		}
		//echo __METHOD__.': '.$this->content.'<br />'."\n";
	}

	function getHeader() {
		$content = '';
		$index = Index::getInstance();
		$index->addJQuery();
		$index->addJQueryUI();
		$content .= '<script src="vendor/bergie/create-gh-pages/js/deps/rangy-core-1.2.3.js"></script>';
		$content .= '<script src="vendor/bergie/create-gh-pages/js/deps/hallo.js"></script>';
		//$content .= '<script src="nadlib/js/contentEditable.js"></script>';	// need enable button
		$content .= '<script src="js/nadlibCMS.js"></script>';
		$index->footer[__CLASS__] = $content;
		return $content;
	}

	function store() {
		$html = html_entity_decode($this->content);
		file_put_contents($this->filename, $html);
		//echo __METHOD__.': '.$this->content.'<br />'."\n";
	}

	function __destruct() {
		//$this->store();
	}

	function __toString() {
		return ($this->content);	// don't add nl2br()
	}

	/**
	 * Make sure $saveURL ends with "=" so that $this->file is correctly appended to it
	 * @param $saveURL
	 * @return string
	 */
	function render($saveURL) {
		$content = '<div class="editable" data-save-url="'.$saveURL.urlencode($this->file).'">'.
			$this->__toString().
		'</div>';
		return $content;
	}

}
