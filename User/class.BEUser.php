<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser extends UserBase
{

	/**
	 * Loaded from config.yaml
	 * @var array
	 */
	public $acl = array();

	function __construct($id = NULL)
	{
		parent::__construct($id);
		Config::getInstance()->mergeConfig($this);
	}

	function try2login()
	{
		//debug('session_start');
		session_start();
	}

	function can($something)
	{
		return $this->acl[$something];
	}

	function saveLogin()
	{
		$_SESSION[__CLASS__]['login'] = $this->id;
	}

	function isAuth()
	{
		return $_SESSION[__CLASS__]['login'] && ($_SESSION[__CLASS__]['login'] == $this->id);
	}

	function logout()
	{
		unset($_SESSION[__CLASS__]['login']);
	}

	function __destruct()
	{
		// do nothing
	}
}
