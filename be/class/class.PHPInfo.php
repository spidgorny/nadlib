<?php

class PHPInfo extends Controller {

	public $layout = 'none';

	function render() {
		phpinfo();
	}

}
