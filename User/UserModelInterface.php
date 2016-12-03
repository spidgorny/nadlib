<?php

interface UserModelInterface {

	function try2login();

	/**
	 * Implementation may vary.
	 * @return mixed
	 */
	/*function saveLogin();*/

	function isAuth();

	function isAdmin();

	function getLogin();

	function insert(array $data);

	function getAvatarURL();

}
