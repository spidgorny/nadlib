<?php

/**
 * Specific app implementation of the login form.
 * This one renders nothing because the login form is embedded into the template.
 */
class LoginForm extends AjaxLogin {

	protected $secret = 'nadlibSecretPasswordHash';

	function __construct($mode = NULL) {
		parent::__construct($mode);
		$this->secret = md5(json_encode($_ENV));
	}

	function __render() {
		return '';
	}

	function loginAction() {
		//debug($this->request);
		$content = '';
		$username = $this->request->getTrim('username');
		$password = $this->request->getTrim('password');
		$passwordHash = $this->secret;
		//debug($passwordHash);
		if ($username == 'nadlib' && $password == $passwordHash) {
			$this->user->saveLogin($username, $passwordHash);
			$content .= '<div class="message">'.__('You are logged in.').'</div>';
			$content .= $this->menuAction();
		} else {
			$content .= '<div class="error">'.__('Wrong login or password.').'</div>';
			$desc = $this->getLoginDesc();
			$desc['username']['value'] = $username;
			$desc['password']['cursor'] = true;
			$content .= $this->formAction($desc);
		}
		return $content;
	}

}
