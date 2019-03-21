<?php

/**
 * Documentation for users
 * TYPO3User -> User (app) -> UserBase -> OODBase
 * TYPO3User -> UserBase -> OODBase
 * PlainSessionUser -> User (app) -> UserBase -> OODBase
 * SessionUser -> PlainSessionUser -> User (app) -> UserBase -> OODBase
 */

class TYPO3User extends UserBase
{

	/**
	 * @var array
	 */
	public $t3user;

	function __construct($id = NULL)
	{
		parent::__construct($id);
		$this->t3user = $GLOBALS["TSFE"]->fe_user;    // set to be_user if you need
	}

	function getPref($key)
	{
		return $this->t3user->getKey('user', $key);
	}

	function setPref($key, $val)
	{
		$this->t3user->setKey('user', $key, $val);
		$this->t3user->storeSessionData();
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
}
