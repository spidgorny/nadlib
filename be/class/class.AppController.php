<?php

class AppController extends Controller {

	function __construct() {
		parent::__construct();
		$this->layout = new Wrap('<div class="span10">', '</div>');
	}
}
