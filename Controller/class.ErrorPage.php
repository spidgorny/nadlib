<?php

class ErrorPage extends Controller {

	function render() {
		debug($_REQUEST);
	}

}