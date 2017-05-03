<?php

/**
 * Some places require a user object which does nothing if you're not logged-in
 */

class NoUser extends UserBase {

	/**
	 * @var Preferences|MockPreferences
	 */
	public $prefs;

	/**
	 * @var AccessRights
	 */
	public $access;

	function __construct() {
		$this->prefs = new MockPreferences($this);
	}

	function can() {
		return false;
	}

	function renderMessages() {
		return '';
	}

	function getPref($key) {
		return NULL;
	}

	function setPref($key, $val) {
		return NULL;
	}

	/**
	 * @return null
	 */
	function getUnreadMessages() {
		return NULL;
	}

	function getAllSettings() {
		return [];
	}

	function getSelfAndBackupID() {
		return [$this->id];
	}

	function getAllSubordinates() {
		return [];
	}

	function try2login() {

	}

	function isAdmin() {
		return false;
	}

	function getLogin() {
		return 'nouser';
	}

	function getAvatarURL() {
		return 'avatar.png';
	}

}
