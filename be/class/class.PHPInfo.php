<?php

class PHPInfo extends AppController {

	public $layout = 'none';

	function render() {
		phpinfo();
	}

}
