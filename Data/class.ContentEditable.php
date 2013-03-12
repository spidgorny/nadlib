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
		$this->filename = 'FAQ/'.$this->file.'.txt';
		$this->content = @file_get_contents($this->filename);
		if (!$this->content) {
			$this->content = '&nbsp;';
		}
		//echo __METHOD__.': '.$this->content.'<br />'."\n";
	}

	function getHeader() {
		$content = '';
		$content .= '<script src="vendor/bergie/create-gh-pages/js/deps/jquery-1.7.1.min.js"></script>';
		$content .= '<script src="vendor/bergie/create-gh-pages/js/deps/jquery-ui-1.8.18.custom.min.js"></script>';
		$content .= '<script src="vendor/bergie/create-gh-pages/js/deps/rangy-core-1.2.3.js"></script>';
		$content .= '<script src="vendor/bergie/create-gh-pages/js/deps/hallo.js"></script>';
		$content .= '<script src="js/contentEditable.js"></script>';
		return $content;
	}

	function store() {
		file_put_contents($this->filename, $this->content);
		//echo __METHOD__.': '.$this->content.'<br />'."\n";
	}

	function __destruct() {
		//$this->store();
	}

	function __toString() {
		return nl2br($this->content);
	}

	/**
	 * Make sure $saveURL ends with "=" so that $this->file is correctly appended to it
	 * @param $saveURL
	 * @return string
	 */
	function render($saveURL) {
		$content = '<div class="editable" data-save-url="'.$saveURL.$this->file.'">
			'.$this->__toString().'
		</div>';
		return $content;
	}

}
