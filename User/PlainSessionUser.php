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
	 * @var Session
	 */
	var $session;

	/**
	 * @param int $id
	 * @param null $session
	 */
	function __construct($id = NULL, $session = null) {
		if (!Request::isCLI()) {
			//debug('session_start');
			@session_start();
		} else {
			$_SESSION = array();
		}
		$this->session = $session ?: new Session(get_class($this));
		parent::__construct($id);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	function getPref($name) {
		return $this->session->get($name);
	}

	function setPref($name, $value) {
		$this->session->save($name, $value);
	}

	function getAllPrefs() {
		return $this->session->getAll();
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
