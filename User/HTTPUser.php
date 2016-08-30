<?php

class HTTPUser extends UserBase {

	protected $login;
	protected $password;

	function __construct() {
		$this->login = $_SERVER['PHP_AUTH_USER'];
		$this->password = $_SERVER['PHP_AUTH_PASSWORD'];
	}

	function __toString() {
		return $this->login;
	}

}
