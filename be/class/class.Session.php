<?php

class Session extends AppController {

	function render() {
		return getDebug($_SESSION);
	}

}
