<?php

class UnitTestReport extends AppControllerBE {

	var $htmlRoot = '../tests/reports/';

	var $file = 'index.html';

	function __construct() {
		parent::__construct();
		$this->file = $this->request->getCoalesce('file', $this->file);
	}

	function render() {
		$content = [];
		if (file_exists($this->htmlRoot . $this->file)) {
			$report = new View($this->htmlRoot . $this->file);
			$content[] = '<link rel="stylesheet" href="'.$this->nadlibFromDocRoot.'tests/reports/css/style.css">';
			$content[] = '<base href="'.$this->request->getLocation().$this->nadlibFromDocRoot.'be/?c=UnitTestReport&file=" />';
			$content[] = '<div class="fixBaseHref">'.$report.'</div>';
			$this->index->addJS($this->nadlibFromDocRoot.'/be/js/fixBaseHref.js');
		} else {
			$this->index->error('phpunit report is not generated');
		}
		return implode("\n", $content);
	}

}
