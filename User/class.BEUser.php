<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser extends UserBase {

	/**
	 * Loaded from config.yaml
	 * @var array
	 */
	public $acl = array(

	);

	function __construct($id = NULL) {
		parent::__construct($id);
		Config::getInstance()->mergeConfig($this);
		$this->try2login();
	}

	function try2login() {
		debug('session_start');
		session_start();
	}

	function can($something) {
		return $this->acl[$something];
	}

	function saveLogin($username, $passwordHash) {
		$_SESSION[__CLASS__]['login'] = $username;
	}

	function isAuth() {
		return !!$_SESSION[__CLASS__]['login'];
	}

	function logout() {
		unset($_SESSION[__CLASS__]['login']);
	}

}
