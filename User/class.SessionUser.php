<?php

/**
 * SessionUser is stored in the database and has an ID even though the login is stored in the session.
 */
class SessionUser extends PlainSessionUser {

	function __construct($id = NULL) {
		parent::__construct($id);
		$this->autologin(); // the main difference of SessionUser
	}

	function autologin() {
		//debug($_SESSION);
		if ($login = $_SESSION[__CLASS__]['login']) {
			$inSession = $this->checkPassword($login, $_SESSION[__CLASS__]['password']);
			if ($inSession) {
				$this->findInDB(array('email' => $login));
			} else {
				//throw new Exception('You are not logged in. Nevermind, you can do it later.');
			}
		}
	}

	function autoCreate($email) {
		// we go here only if not logged in
		// if not a new email and no password we need to ask for password
		$u = new User(); // not to mess-up with current object
		$u->findInDB(array('email' => $email));
		if ($u->id) {
			throw new Exception(__('Your e-mail is known to the system. Please enter a password.<br>
			<a href="?c=ForgotPassword">Forgot password?</a>'));
		} else {
			$password = rand(1000000, 9999999);
			if (DEVELOPMENT) {
				print 'Generated password: '.$password;
			}
			$this->insert(array(
				'email' => $email,
				'password' => $password,
			));

			$dataObj = new stdClass();
			$dataObj->email = $email;
			$dataObj->password = $password;
			mail($email, 'Account created', new View('emailNewAutoAccount.phtml', $dataObj), "From: ".$GLOBALS['i']->mailFrom);

			$this->saveLogin($email, md5($password));
			//$this->autologin();
		}
	}

	/**
	 * Session only stores MD5'ed passwords! It can't be otherwise!
	 * This is a success function which loads user data as well.
	 *
	 * @param unknown_type $email
	 * @param unknown_type $password - hash
	 * @throws Exception
	 */
	function saveLogin($email, $password) {
		if (strlen($password) != 32) {
			throw new Exception(__METHOD__.': supplied password is not hash.');
		} else {
			$_SESSION[__CLASS__]['login'] = $email;
			$_SESSION[__CLASS__]['password'] = $password;
			$this->findInDB(array('email' => $email));
			if (!$this->id) {
				//debug($this->data, 'saveLogin');
				throw new Exception('Login/password matched, but DB retrieval not.');
			}
		}
	}

	function logout() {
		unset($_SESSION[__CLASS__]);
		User::unsetInstance($GLOBALS['i']->user->id);
		unset($GLOBALS['i']->user);
		$GLOBALS['i']->user = new User(); // make new anonymous user - does it work?
	}

}
