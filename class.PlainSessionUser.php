<?php

class PlainSessionUser extends User {

	function __construct() {
		session_start();
		parent::__construct();
	}

	function getPref($name) {
		return $_SESSION[$name];
	}

	function setPref($name, $value) {
		$_SESSION[$name] = $value;
	}

}
