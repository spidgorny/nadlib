<?php

/**
 * SessionUser is stored in the database and has an ID even though the login is stored in the session.
 */
class SessionUser extends PlainSessionUser {

	function __construct($id = NULL) {
		parent::__construct($id);
		if (get_class($this) == 'LoginUser') {
			$this->autologin(); // the main difference of SessionUser from PlainSessionUser
		}
	}

	function autologin() {
		$class = get_called_class();
		if (ifsetor($_SESSION[$class]) && ($login = $_SESSION[$class]['login'])) {
			$inSession = $this->checkPassword($login, $_SESSION[$class]['password']);
			if ($inSession) {
				//$this->findInDB(array('email' => $login));
				$this->init($login);
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
	 * @param string $email
	 * @param string $password - hash
	 * @throws Exception
	 */
	function saveLogin($email = NULL, $password = NULL) {
		if (strlen($password) != 32) {
			throw new Exception(__METHOD__.': supplied password is not hash.');
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

	function logout() {
		$class = get_called_class();
		unset($_SESSION[$class]);
		session_regenerate_id(true);
		session_destroy();
	}

}
