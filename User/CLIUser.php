<?php

class CLIUser implements UserModelInterface
{

	public function try2login($login, $email = null)
	{
		// TODO: Implement try2login() method.
	}

	public function isAuth()
	{
		// TODO: Implement isAuth() method.
	}

	public function isAdmin()
	{
		// TODO: Implement isAdmin() method.
	}

	public function getLogin()
	{
		// TODO: Implement getLogin() method.
	}

	public function insert(array $data)
	{
		// TODO: Implement insert() method.
	}

	public function getAvatarURL()
	{
		// TODO: Implement getAvatarURL() method.
	}

	public function prefs()
	{
		// TODO: Implement prefs() method.
	}

	public function can($acl)
	{
		return false;
	}

	public function getID()
	{
		// TODO: Implement getID() method.
	}

	public function getAllSettings()
	{
		// TODO: Implement getAllSettings() method.
	}

	public function getSetting($key)
	{
		// TODO: Implement getSetting() method.
	}

	public function getGroup()
	{
		// TODO: Implement getGroup() method.
	}

	public function getGravatarURL($size = 32)
	{
		// TODO: Implement getGravatarURL() method.
	}

	public function updatePassword($newPassword)
	{
		// TODO: Implement updatePassword() method.
	}
}
