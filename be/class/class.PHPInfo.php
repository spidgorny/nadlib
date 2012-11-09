<?php

class PHPInfo extends AppControllerBE {

	public $layout = 'none';

	function render() {
		phpinfo();
	}

}
