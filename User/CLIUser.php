<?php

class CLIUser implements UserModelInterface
{

	public function try2login($login, $email = null): void
	{
		// TODO: Implement try2login() method.
	}

	public function isAuth(): void
	{
		// TODO: Implement isAuth() method.
	}

	public function isAdmin(): void
	{
		// TODO: Implement isAdmin() method.
	}

	public function getLogin(): void
	{
		// TODO: Implement getLogin() method.
	}

	public function insert(array $data): void
	{
		// TODO: Implement insert() method.
	}

	public function getAvatarURL(): void
	{
		// TODO: Implement getAvatarURL() method.
	}

	public function prefs(): void
	{
		// TODO: Implement prefs() method.
	}

	public function can($acl): bool
	{
		return false;
	}

	public function getID(): void
	{
		// TODO: Implement getID() method.
	}

	public function getAllSettings(): void
	{
		// TODO: Implement getAllSettings() method.
	}

	public function getSetting($key, $default = null)
	{
		return $default;
	}

	public function getGroup(): void
	{
		// TODO: Implement getGroup() method.
	}

	public function getGravatarURL($size = 32): void
	{
		// TODO: Implement getGravatarURL() method.
	}

	public function updatePassword($newPassword): void
	{
		// TODO: Implement updatePassword() method.
	}

	public function getPref($pref, $default = null)
	{
		// TODO: Implement getPref() method.
	}

	public function getDepartment(): ?Department
	{
		return null;
	}

	public function getName(): string
	{
		return 'CLI User';
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
