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
	static protected $instance;

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
	}

	public function getAllPrefs()
	{
		return $this->session->getAll();
	}

	public function isAuth()
	{
		if (phpversion() >= 5.4) {
			return session_status() == PHP_SESSION_ACTIVE;    // PHP 5.4
		} else {
			return true;
		}
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
