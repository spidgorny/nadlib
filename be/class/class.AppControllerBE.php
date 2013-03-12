<?php

class AppControllerBE extends Controller {

	function __construct() {
		parent::__construct();
		$this->layout = new Wrap('<div class="span10">', '</div>');
		$this->index = IndexBE::getInstance();
	}

	function log($a) {
		echo $a.'<br />'."\n";
	}

}
