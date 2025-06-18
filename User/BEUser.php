<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser implements UserModelInterface
{

	public $id;
	/**
	 * Loaded from config.json
	 * @var array
	 */
	public $acl = [];

	public function __construct($id = null)
	{
		$this->id = $id;
		if (class_exists('Config')) {
			Config::getInstance()->mergeConfig($this);
		}
	}

	public function try2login($user, $password = null): void
	{
		if (session_status() != PHP_SESSION_ACTIVE && !Request::isCLI() && !headers_sent()) {
			llog('session_start in BEUser');
			header('X-Session-Start: ' . __METHOD__);
			session_start();
		}
	}

	public function can($something)
	{
		return $this->acl[$something];
	}

	public function saveLogin(): void
	{
		$_SESSION[__CLASS__]['login'] = $this->getID();
	}

	public function getID()
	{
	}

	public function isAuth(): bool
	{
		return isset($_SESSION[__CLASS__]['login']) && ($_SESSION[__CLASS__]['login'] == $this->getID());
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

	public function getSetting($key, $default = null)
	{
		return $default;
	}

	public function updatePassword($newPassword): void
	{
		// TODO: Implement updatePassword() method.
	}

	public function getDepartment()
	{
		return null;
	}

	public function insert(array $data)
	{
	}

	public function getPref($pref, $default = null)
	{
		return $default;
	}

	public function getGravatarURL($size = 32)
	{
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

	public function isJuniorTechnician()
	{
		// TODO: Implement isJuniorTechnician() method.
	}

	public function getPerson()
	{
		// TODO: Implement getPerson() method.
	}

	public function setPref($key, $value)
	{
		// TODO: Implement setPref() method.
	}
}
