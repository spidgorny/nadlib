<?php

interface UserModelInterface
{

	public function try2login();

	/**
	 * Implementation may vary.
	 * @return mixed
	 */
	/*function saveLogin();*/

	public function isAuth();

	public function isAdmin();

	public function getLogin();

	public function insert(array $data);

	public function getAvatarURL();

	public function prefs();

	public function can($rights);

}
