<?php

/**
 * A user not backed by a database.
 * It may come from HTTP login/password or be anonymous user from CLI.
 * Should not have any persistence methods. Only getters.
 */
interface SomeKindOfUser
{

	public function isAuth();

	public function isAdmin();

	public function getLogin();

	/**
	 * @param string $acl
	 * @return bool
	 */
	public function can($acl);

	public function getID();

	public function getData(): array;

	public function getGravatarURL($size = 32);

	public function getName(): string;

}

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

	#[\ReturnTypeWillChange]
	public function getDepartment(): ?Department;

}
