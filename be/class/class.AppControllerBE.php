<?php

class AppControllerBE extends Controller {

	var $forceCronjob = false;

	function __construct() {
		parent::__construct();
		$this->layout = new Wrap('<div class="span10">', '</div>');
		$this->index = IndexBE::getInstance();
		//debug($this->request->getAll());
		if ($this->request->getBool('force')) {
			$this->forceCronjob = true;
		}
	}

	function log($a) {
		echo $a.'<br />'."\n";
	}

}
