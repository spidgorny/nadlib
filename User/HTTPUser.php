<?php

class HTTPUser extends UserBase
{

	protected $login;

	protected $password;

	public function __construct()
	{
		$this->login = $_SERVER['PHP_AUTH_USER'];
		$this->password = $_SERVER['PHP_AUTH_PASSWORD'];
	}

	public function __toString(): string
	{
		return $this->login;
	}

	/**
     * @param string $login
     * @param string $email
     */
    public function try2login($login, $email = null): void
	{
		// TODO: Implement try2login() method.
	}

	public function isAdmin(): void
	{
		// TODO: Implement isAdmin() method.
	}

	public function getLogin(): void
	{
		// TODO: Implement getLogin() method.
	}

	public function getAvatarURL(): void
	{
		// TODO: Implement getAvatarURL() method.
	}

	/**
     * @param string $acl
     */
    public function can($acl): bool
	{
		return false;
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

	public function getDepartment(): ?Department
	{
		return null;
	}

}
