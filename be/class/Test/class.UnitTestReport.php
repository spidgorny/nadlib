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
		$report = new View($this->htmlRoot . $this->file);
		$content[] = '<base href="'.$this->request->getLocation().'vendor/spidgorny/nadlib/be/?c=UnitTestReport&file=" />';
		$content[] = '<div class="fixBaseHref">'.$report.'</div>';
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot.'/be/js/fixBaseHref.js');
		return implode("\n", $content);
	}

}
