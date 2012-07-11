<?php

class PlainSessionUser {

	function __construct() {
		session_start();
	}

	function getPref($name) {
		return $_SESSION[$name];
	}

	function setPref($name, $value) {
		$_SESSION[$name] = $value;
	}

}
