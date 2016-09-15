<?php

/**
 * Documentation for users
 * TYPO3User -> User (app) -> UserBase -> OODBase
 * TYPO3User -> UserBase -> OODBase
 * PlainSessionUser -> User (app) -> UserBase -> OODBase
 * SessionUser -> PlainSessionUser -> User (app) -> UserBase -> OODBase
 */

class TYPO3Module extends UserBase {

	protected $module;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	public $t3user;

	function __construct($module) {
		parent::__construct();
		$this->module = $module;
		$this->t3user = $GLOBALS['BE_USER'];
	}

	function getPref($key) {
		//d($this->t3user->uc);
		return $this->t3user->uc['moduleData'][$this->module][$key];
	}

	function setPref($key, $val) {
		$this->t3user->uc['moduleData'][$this->module][$key] = $val;
		$this->t3user->pushModuleData($this->module, $this->t3user->uc['moduleData'][$this->module], false);
	}

}
