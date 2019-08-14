<?php

class LDAPUser extends UserBase
{

	public $UserID;
	public $UserName;

	/**
	 * @var array
	 */
	public $data;

	function __construct(array $ldapInfo = array())
	{
		$this->initLDAP($ldapInfo);
	}

	function initLDAP(array $ldapInfo = array())
	{
		$goodKeys = array_filter(array_keys($ldapInfo), 'is_string');
		$ldapInfo = array_intersect_key($ldapInfo, array_flip($goodKeys));
		$this->data = $ldapInfo;
		$this->id = $this->data['uid'][0];
		$this->UserID = $this->data['uid'][0];
		$this->UserName = $this->data['fullname'][0];
	}

	function getName()
	{
		return $this->UserName . ' (' . $this->UserID . ') <' . $this->data['mail'][0] . '>';
	}

	function try2login()
	{
		if ($_SESSION['user']) {
			$this->id = $_SESSION['user']->id;
			$this->data = $_SESSION['user']->data;
			$this->UserID = $_SESSION['user']->UserID;
			$this->UserName = $_SESSION['user']->UserName;
		}
	}

	function saveLogin()
	{
		$obj = new stdClass();
		$obj->id = $this->id;
		$obj->data = $this->data;
		$obj->UserID = $this->UserID;
		$obj->UserName = $this->UserName;
		$_SESSION['user'] = $obj;
	}

	function logout()
	{
		unset($_SESSION['user']);
	}

	/**
	 * Simplifies $this->data for display
	 * @return array
	 */
	function getInfo()
	{
		$simpleData = array();
		foreach ($this->data as $field => $data) {
			if (is_array($data) && $data['count'] == 1) {
				$simpleData[$field] = $data[0];
			} else if (is_array($data)) {
				unset($data['count']);
				$simpleData[$field] = $data;
			}
		}
		unset($simpleData['zcmsharedsecret']);
		unset($simpleData['zenzfdversion']);
		unset($simpleData['dirxml-passwordsyncstatus']);
		unset($simpleData['protocom-sso-security-prefs']);
		unset($simpleData['protocom-sso-entries']);
		unset($simpleData['nnmclientsettings']);
		unset($simpleData['nnmcontactlist']);
		unset($simpleData['sasloginsecret']);
		return $simpleData;
	}

}
