<?php

class LDAPUser extends UserBase {

	public $UserID;
	public $UserName;

	/**
	 * @var array
	 */
	public $data;

	function __construct(array $ldapInfo) {
		$goodKeys = array_filter(array_keys($ldapInfo), 'is_string');
		$ldapInfo = array_intersect_key($ldapInfo, array_flip($goodKeys));
		$this->data = $ldapInfo;
		$this->UserID = $this->data['uid'][0];
		$this->UserName = $this->data['fullname'][0];
	}

	function getName() {
		return $this->UserName.' ('.$this->UserID.') <'.$this->data['mail'][0].'>';
	}

}
