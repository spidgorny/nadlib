<?php

class Session {

	var $prefix;

	function __construct($prefix = NULL) {
		$this->prefix = $prefix;
	}

	static function isActive() {
		//debug(session_id(), !!session_id(), session_status(), $_SESSION['FloatTime']);
		if (function_exists('session_status')) {
			// somehow PHP_SESSION_NONE is the status when $_SESSION var exists
			return in_array(session_status(), [PHP_SESSION_ACTIVE, PHP_SESSION_NONE]);
		} else {
			return !!session_id();
		}
	}

	function get($key) {
		if ($this->prefix) {
			return ifsetor($_SESSION[$this->prefix][$key]);
		} else {
			return ifsetor($_SESSION[$key]);
		}
	}

	function save($key, $val) {
		if ($this->prefix) {
			$_SESSION[$this->prefix][$key] = $val;
		} else {
			$_SESSION[$key] = $val;
		}
	}

	function __get($name) {
		return $this->get($name);
	}

	function __set($name, $value) {
		$this->save($name, $value);
	}

	public function clearAll() {
		unset($_SESSION[$this->prefix]);
	}

}
