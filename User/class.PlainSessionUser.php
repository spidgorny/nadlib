<?php

class PlainSessionUser extends User {

	function __construct() {
		if (!Request::isCLI()) {
			session_start();
		} else {
			$_SESSION = array();
		}
		parent::__construct();
	}

	function getPref($name) {
		return $_SESSION[$name];
	}

	function setPref($name, $value) {
		$_SESSION[$name] = $value;
	}

	function isAuth() {
		return true;
		return session_status() == PHP_SESSION_ACTIVE;	// PHP 5.4
	}

}
