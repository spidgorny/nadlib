<?php

class PlainSessionUser extends UserBase
{

	/**
	 * @var PlainSessionUser
	 */
	static protected $instance;

	function __construct()
	{
		if (!Request::isCLI()) {
			//debug('session_start');
			@session_start();
		} else {
			$_SESSION = array();
		}
		parent::__construct();
	}

	function getPref($name)
	{
		return $_SESSION[$name];
	}

	function setPref($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	function isAuth()
	{
		if (phpversion() >= 5.4) {
			return session_status() == PHP_SESSION_ACTIVE;    // PHP 5.4
		} else {
			return true;
		}
	}

	function __toString()
	{
		return session_id();
	}

	public static function getInstance($id)
	{
		return self::$instance
			? self::$instance
			: self::$instance = new self($id);
	}

	function try2login()
	{
		// session_start
	}

}
