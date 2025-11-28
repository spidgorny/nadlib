<?php

interface UserModelInterface extends SomeKindOfUser
{

	/**
	 * @param string $login
	 * @param string $email
	 * @return mixed
	 */
	public function try2login($login, $email = null);

	/**
	 * Implementation may vary.
	 * @return mixed
	 */
	/*function saveLogin();*/

	public function insert(array $data);

	public function getAvatarURL();

	public function prefs();

	public function getAllSettings();

	public function getSetting($key, $default = null);

	public function setSetting($key, $val);

	public function getGroup();

	public function updatePassword($newPassword);

	//public function findInDB(array $where = [], $orderByLimit = '', $selectPlus = null);

	public function getPref($pref, $default = null);

	public function getDepartment();

	public function isJuniorTechnician();

	public function getPerson();

	public function getCity();

}
