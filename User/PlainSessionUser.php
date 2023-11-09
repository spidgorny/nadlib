<?php

use nadlib\HTTP\Session;

/**
 * Class PlainSessionUser
 * extends User in order to have a dependency on the application
 */
class PlainSessionUser extends User
{

	/**
	 * @var PlainSessionUser
	 */
	protected static $instance;

	/**
	 * @var Session
	 */
	protected $session;

	/**
	 * @param int $id
	 * @param Session $session
	 * @throws Exception
	 */
	public function __construct($id = null, $session = null)
	{
		if (!Request::isCLI()) {
			//debug('session_start');
			@session_start();
		} else {
			$_SESSION = [];
		}
		$this->session = $session ?: new Session(get_class($this));
		parent::__construct($id);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getPref($name, $default = null)
	{
		return $this->session->get($name);
	}

	public function setPref($name, $value)
	{
		$this->session->save($name, $value);
		return $value;
	}

	public function getAllPrefs()
	{
		return $this->session->getAll();
	}

	public function isAuth()
	{
		if (PHP_VERSION >= 5.4) {
			return session_status() === PHP_SESSION_ACTIVE;    // PHP 5.4
		}

		return true;
	}

	public function __toString()
	{
		$default = parent::__toString();
		return ifsetor($default, session_id()) . '';
	}

	public function try2login($login, $email = null)
	{
		// session_start
	}

}
