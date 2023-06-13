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
		$files = glob('pages/'.$this->file.'.*');
		$this->filename = $files[0];
		$this->content = @file_get_contents($this->filename);
		if (!$this->content) {
			$this->content = '&nbsp;';
		}
		//echo __METHOD__.': '.$this->content.'<br />'."\n";
	}

	function getHeader() {
		$index = Index::getInstance();
		$index->addJQuery();
		$index->addJQueryUI();
		$index->addJS("components/rangy/rangy-core.js");
		if (DEVELOPMENT) {
			$index->addJS("components/hallo/hallo.js");
		} else {
			$index->addJS("components/hallo/hallo.min.js");
		}
		$index->addJS("vendor/spidgorny/nadlib/js/contentEditable.js");
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
		$ext = pathinfo($this->filename, PATHINFO_EXTENSION);
		switch ($ext) {
			case 'txt':
				$content = nl2br(htmlspecialchars($this->content));
				break;
			case 'html':
				$content = $this->content;
				$content = str_replace('src="', 'src="'.dirname($this->filename).'/', $content);	// IMG
				break;
			case 'md':
				$md = new Markdown($this->filename);
				$content = $md->render();
				break;
			default:
				throw new Exception(__METHOD__);
		}
		return $content;
	}

	/**
	 * Make sure $saveURL ends with "=" so that $this->file is correctly appended to it
	 * @param string $saveURL
	 * @return string
	 * @throws Exception
	 */
	function render($saveURL) {
		$content = '<div class="editable" data-save-url="'.$saveURL.urlencode($this->file).'">'.
			$this->__toString().
		'</div>';
		return $content;
	}

}
