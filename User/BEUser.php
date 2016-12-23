<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser extends UserBase {

	/**
	 * Loaded from config.json
	 * @var array
	 */
	public $acl = array(

	);

	function __construct($id = NULL) {
		parent::__construct($id);
		if (class_exists('Config')) {
			Config::getInstance()->mergeConfig($this);
		}
	}

	function try2login() {
		//debug('session_start');
		if (session_status() != PHP_SESSION_ACTIVE && !Request::isCLI() && !headers_sent()) {
			session_start();
		}
	}

	function can($something) {
		return $this->acl[$something];
	}

	function saveLogin() {
		$_SESSION[__CLASS__]['login'] = $this->id;
	}

	function isAuth() {
		return isset($_SESSION[__CLASS__]['login']) && ($_SESSION[__CLASS__]['login'] == $this->id);
	}

	function logout() {
		unset($_SESSION[__CLASS__]['login']);
	}

	function __destruct() {
		// do nothing
	}

	function isAdmin()
	{
		return true;
	}

	function getLogin()
	{
		return 'Nadlib Admin';
	}

	function getAvatarURL()
	{
		return NULL;
	}

}
