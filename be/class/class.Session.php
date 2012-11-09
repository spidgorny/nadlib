<?php

class Session extends AppControllerBE {

	function render() {
		return getDebug($_SESSION);
	}

}
