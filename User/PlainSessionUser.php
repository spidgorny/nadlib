<?php

use nadlib\HTTP\Session;

/**
 * Class PlainSessionUser
 * extends User in order to have a dependency on the application
 */
class PlainSessionUser extends UserBase
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
			header('X-Session-Start: ' . __METHOD__);
			session_start();
		} else {
			$_SESSION = [];
		}

		$this->session = $session ?: new Session(get_class($this));
		parent::__construct($id, $this->db);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getPref($name, $default = null)
	{
		return $this->session->get($name);
	}

	public function setPref($name, $value): void
	{
		$this->session->save($name, $value);
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

	public function __toString(): string
	{
		$default = parent::__toString();
		return ifsetor($default, session_id()) . '';
	}

	public function try2login($login, $email = null): void
	{
		// session_start
	}

	public function getSetting($key, $default = null)
	{
		return $default;
	}

	public function prefs(): array
	{
		return [];
	}

	public function isAdmin(): bool
	{
		return false;
	}

	public function getLogin()
	{
		return null;
	}

	public function getGroup()
	{
		return null;
	}

	public function getAvatarURL()
	{
		return null;
	}

	public function getAllSettings()
	{
		return null;
	}

	public function can($acl): bool
	{
		return false;
	}

	public function updatePassword($newPassword): void
	{
		// TODO: Implement updatePassword() method.
	}

	public function getDepartment(): ?Department
	{
		return null;
	}

	public function getName(): string
	{
		return '';
	}

	public function setSetting($key, $val)
	{
		// TODO: Implement setSetting() method.
	}

	public function getData(): array
	{
		return [];
	}
}
