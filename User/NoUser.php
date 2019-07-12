<?php

/**
 * Some places require a user object which does nothing if you're not logged-in
 */
class NoUser extends UserBase implements UserModelInterface
{

	/**
	 * @var Preferences|MockPreferences
	 */
	public $prefs;

	/**
	 * @var AccessRightsInterface
	 */
	public $access;

	public function __construct()
	{
		$this->prefs = new MockPreferences($this);
	}

	public function can($name)
	{
		return false;
	}

	public function renderMessages()
	{
		return '';
	}

	public function getPref($key)
	{
		return null;
	}

	public function setPref($key, $val)
	{
		return null;
	}

	/**
	 * @return null
	 */
	public function getUnreadMessages()
	{
		return null;
	}

	public function getAllSettings()
	{
		return [];
	}

	public function getSelfAndBackupID()
	{
		return [$this->id];
	}

	public function getAllSubordinates()
	{
		return [];
	}

	public function try2login($user, $password = null)
	{
	}

	public function isAdmin()
	{
		return false;
	}

	public function getLogin()
	{
		return 'somebody';
	}

	public function getAvatarURL()
	{
		return 'http://avatar.com/';
	}

	public function prefs()
	{
		// TODO: Implement prefs() method.
	}

	public function getGroup()
	{
		// TODO: Implement getGroup() method.
	}
}
