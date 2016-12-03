<?php

interface UserModelInterface {

	function try2login();

	function isAuth();

	function isAdmin();

	function getLogin();

	function insert(array $data);

	function getAvatarURL();

}
