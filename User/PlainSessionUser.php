<?php

/**
 * Class PlainSessionUser
 * extends User in order to have a dependency on the application
 */
class PlainSessionUser extends User {

	/**
	 * @var PlainSessionUser
	 */
	static protected $instance;

	/**
	 * @param int $id
	 */
	function __construct($id = NULL) {
		if (!Request::isCLI()) {
			//debug('session_start');
			@session_start();
		} else {
			$_SESSION = array();
		}
		parent::__construct($id);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	function getPref($name) {
		return ifsetor($_SESSION[$name]);
	}

	function setPref($name, $value) {
		$_SESSION[$name] = $value;
	}

	function getAllPrefs() {
		return $_SESSION;
	}

	function isAuth() {
		if (phpversion() >= 5.4) {
			return session_status() == PHP_SESSION_ACTIVE;	// PHP 5.4
		} else {
			return true;
		}
	}

	function __toString() {
		$default = parent::__toString();
		return ifsetor($default, session_id()).'';
	}

	function try2login() {
		// session_start
	}

}
