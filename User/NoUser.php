<?php

/**
 * Some places require a user object which does nothing if you're not logged-in
 */
class NoUser implements UserModelInterface
{

	/**
	 * @var Preferences|MockPreferences
	 */
	public $prefs;

	/**
	 * @var AccessRightsInterface
	 */
	public $access = [];

	public function __construct()
	{
		$this->prefs = new MockPreferences($this);
	}

	public function setAccess($name, $value): void
	{
		$this->access[$name] = $value;
	}

	public function can($name)
	{
		return $this->access[$name];
	}

	public function renderMessages(): string
	{
		return '';
	}

	public function getPref($pref, $default = null)
	{
		return $this->prefs->get($pref, $default);
	}

	public function setPref($key, $val): void
	{
		$this->prefs->set($key, $val);
	}

	public function getUnreadMessages()
	{
		return null;
	}

	public function getAllSettings(): array
	{
		return [];
	}

	public function getSelfAndBackupID(): array
	{
		return [$this->getID()];
	}

	public function getID()
	{
		return null;
	}

	public function getAllSubordinates(): array
	{
		return [];
	}

	public function try2login($user, $password = null)
	{
	}

	public function isAdmin(): bool
	{
		return false;
	}

	public function getLogin(): string
	{
		return 'nobody';
	}

	public function getAvatarURL(): string
	{
		return 'http://avatar.com/';
	}

	public function getAllPrefs()
	{
		return $this->prefs()->getData();
	}

	public function getData(): array
	{
		return [];
	}

	public function prefs()
	{
		return $this->prefs;
	}

	public function getPerson()
	{
		return null;
	}

	public function isDev(): bool
	{
		return false;
	}

	public function getGroup(): void
	{
		// TODO: Implement getGroup() method.
	}

	public function loginFromHTTP(): void
	{
		// do nothing, we failed to login with a session
	}

	public function getSetting($key, $default = null)
	{
		return $default;
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
		return 'NoUser';
	}

	public function isAuth()
	{
		return false;
	}

	public function getGravatarURL($size = 32): string
	{
		return '';
	}

	public function insert(array $data)
	{
	}

	public function setSetting($key, $val)
	{
		// TODO: Implement setSetting() method.
	}
}
