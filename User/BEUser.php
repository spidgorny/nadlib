<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser extends UserBase
{

	/**
	 * Loaded from config.json
	 * @var array
	 */
	public $acl = [];

	public function __construct($id = null)
	{
		parent::__construct($id);
		if (class_exists('Config')) {
			Config::getInstance()->mergeConfig($this);
		}
	}

	public function try2login($user, $password = null): void
	{
		//debug('session_start');
		if (session_status() != PHP_SESSION_ACTIVE && !Request::isCLI() && !headers_sent()) {
			llog('session_start in BEUser');
			session_start();
		}
	}

	public function can($something)
	{
		return $this->acl[$something];
	}

	public function saveLogin(): void
	{
		$_SESSION[__CLASS__]['login'] = $this->id;
	}

	public function isAuth(): bool
	{
		return isset($_SESSION[__CLASS__]['login']) && ($_SESSION[__CLASS__]['login'] == $this->id);
	}

	public function logout(): void
	{
		unset($_SESSION[__CLASS__]['login']);
	}

	public function __destruct()
	{
		// do nothing
	}

	public function isAdmin(): bool
	{
		return true;
	}

	public function getLogin(): string
	{
		return 'Nadlib Admin';
	}

	public function getAvatarURL()
	{
		return null;
	}

	public function prefs(): void
	{
		// TODO: Implement prefs() method.
	}

	public function getAllSettings(): void
	{
		// TODO: Implement getAllSettings() method.
	}

	public function getGroup()
	{
		return null;
	}

	public function getSetting($key): void
	{
		// TODO: Implement getSetting() method.
	}

	public function updatePassword($newPassword): void
	{
		// TODO: Implement updatePassword() method.
	}
}
