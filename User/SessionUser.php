<?php

/**
 * SessionUser is stored in the database and has an ID even though the login is stored in the session.
 */
class SessionUser extends PlainSessionUser
{

	public function __construct($id = null)
	{
		parent::__construct($id);
		if (get_class($this) === 'LoginUser') {
			$this->autologin(); // the main difference of SessionUser from PlainSessionUser
		}
	}

	/**
	 * @throws Exception
	 */
	public function autologin()
	{
		$class = get_called_class();
		$login = $_SESSION[$class]['login'];
		if (ifsetor($_SESSION[$class]) && $login) {
			$inSession = $this->checkPassword($login, $_SESSION[$class]['password']);
			if ($inSession) {
				//$this->findInDB(array('email' => $login));
				$this->init($login);
			} else {
				//throw new Exception('You are not logged in. Nevermind, you can do it later.');
			}
		}
	}

	/**
	 * Session only stores MD5'ed passwords! It can't be otherwise!
	 * This is a success function which loads user data as well.
	 *
	 * @param string $email
	 * @param string $password - hash
	 * @throws Exception
	 */
	public function saveLogin($email = null, $password = null)
	{
		if (strlen($password) != 32) {
			throw new Exception(__METHOD__ . ': supplied password is not hash.');
		} else {
			if ($this->id) {
				$class = get_called_class();
				$_SESSION[$class]['login'] = $email;
				$_SESSION[$class]['password'] = $password;
			} else {
				//debug($this->data, 'saveLogin');
				throw new Exception('Login/password matched, but DB retrieval not.');
			}
		}
	}

	public function logout()
	{
		$class = get_called_class();
		unset($_SESSION[$class]);
		session_regenerate_id(true);
		session_destroy();
	}

}
