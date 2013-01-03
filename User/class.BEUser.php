<?php

/**
 * UserBase not User because it's not dependent on the main app login system
 */
class BEUser extends UserBase {

	/**
	 * Loaded from config.yaml
	 * @var array
	 */
	public $acl = array(

	);

	/**
	 * @var publicly visible (cookie) md5 of the password
	 */
	protected $passwordHash;

	function __construct($id = NULL) {
		parent::__construct($id);
		Config::getInstance()->mergeConfig($this);
		$this->passwordHash = md5('someshit');	// replace by some dynamic logic
		$this->try2login();
	}

	function try2login() {
		session_start();
		if ($_SESSION[__CLASS__] == $this->passwordHash) {
			$this->login = 'nadlib';
		} else if ($_REQUEST['login']) {
			if (md5($_REQUEST['password']) == $this->passwordHash) {
				$this->login = 'nadlib';
				$_SESSION[__CLASS__] = $this->passwordHash;
			}
		}
	}

	function can($something) {
		return $this->acl[$something];
	}

}
