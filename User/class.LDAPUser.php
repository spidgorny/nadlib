<?php

class LDAPUser extends UserBase {

	public $UserID;
	public $UserName;

	/**
	 * @var array
	 */
	public $data;

	function __construct(array $ldapInfo = array()) {
		$goodKeys = array_filter(array_keys($ldapInfo), 'is_string');
		$ldapInfo = array_intersect_key($ldapInfo, array_flip($goodKeys));
		$this->data = $ldapInfo;
		$this->id = $this->data['uid'][0];
		$this->UserID = $this->data['uid'][0];
		$this->UserName = $this->data['fullname'][0];
	}

	function getName() {
		return $this->UserName.' ('.$this->UserID.') <'.$this->data['mail'][0].'>';
	}

	function try2login() {
		if ($_SESSION['user']) {
			$this->id = $_SESSION['user']->id;
			$this->data = $_SESSION['user']->data;
			$this->UserID = $_SESSION['user']->UserID;
			$this->UserName = $_SESSION['user']->UserName;
		}
	}

	function saveLogin() {
		$obj = new stdClass();
		$obj->id = $this->id;
		$obj->data = $this->data;
		$obj->UserID = $this->UserID;
		$obj->UserName = $this->UserName;
		$_SESSION['user'] = $obj;
	}

	function logout() {
		unset($_SESSION['user']);
	}

}
