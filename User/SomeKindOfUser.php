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

	public function getPerson();

	public function getPref($key);

	public function setPref($key, $value);

}
