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

	public function __toString()
	{
		return $this->login;
	}

	public function try2login()
	{
		// TODO: Implement try2login() method.
	}

	public function isAdmin()
	{
		// TODO: Implement isAdmin() method.
	}

	public function getLogin()
	{
		// TODO: Implement getLogin() method.
	}

	public function getAvatarURL()
	{
		// TODO: Implement getAvatarURL() method.
	}

	/**
	 * @param string $acl
	 * @return bool
	 */
	public function can($acl)
	{
		// TODO: Implement can() method.
	}
}
