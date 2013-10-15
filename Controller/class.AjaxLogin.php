<?php

class AjaxLogin extends AppController {

	protected $action;

	protected $secret = 'fdhgfjklgfdhj';

	public $mailHeaders = "Content-type: text/plain; charset=\"UTF-8\"\r\nFrom: Wagner-Verlag <info@wagner-verlag.de>\r\n";

	public $openable = true;

	public $withRegister = true;

	/**
	 * Activation message saved to be shown on the center div
	 * @var string
	 */
	protected $message = '';

	/**
	 * @var bool - true to be able to login when you're not yet logged-in
	 */
	public static $public = true;

	/**
	 * Remove to disable jQuery dependancy
	 * @var string
	 */
	public $formMore = 'onsubmit="jQuery(this).ajaxSubmit({
			//function (res) { jQuery(\'#AjaxLogin\').html(res); }
			target: \'#AjaxLogin\',
			//url: \'buch.php\'
			}); return false;"';

	protected $allowedActions = array('login', 'forgotPassword', 'saveRegister', 'activate', 'inlineForm', 'logout');

	function __construct($action = NULL) {
		parent::__construct();
		Config::getInstance()->mergeConfig($this);
		$this->layout = new Wrap('<div class="span10">', '</div>');
		$action = $action ? $action : $this->request->getTrim('action');	// don't reverse this line as it will call mode=login twice
		if ($action) {
			$this->action = $action;
			//debug($this->action);
		}
		//$this->createDB();
	}

	function createDB() {
		$GLOBALS['index']->db->perform("CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `ctime` timestamp NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `mtime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `activated` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
)");
	}

	function dispatchAjax() {
		$content = '';
		if ($this->action) {
			$content .= $this->performAction();
/*			if ($this->mode != 'activate') { // activate is NOT to be processed with AJAX
				header('Content-type: text/html; charset=ISO-8859-1');
				echo $content;
				exit();
			}
*/		} else {
			if ($_SESSION['pageBeforeRegister_show']/* && $_SESSION['pageBeforeRegister']*/) {
				$redirect = $_SESSION['pageBeforeRegister'];
				echo '<div class="absMessage">'.__('Account activated.').'</div>';
				unset($_SESSION['pageBeforeRegister']);
			}
		}
		return $content;
	}

	function performAction($action = NULL) {
		$content = '';
		if ($action = $action ?: $this->action) {
			if (in_array($action, $this->allowedActions) || $this->user->isAuth()) {
				try {
					$cb = $action.'Action';
					$content .= $this->$cb();
				} catch (Exception $e) {
					$content .= '<div class="error">'.__($e->getMessage()).'</div>';
				}
			} else { // prevent profile editing for not logged in
				$content .= '<div class="error">'.__('Not logged in.').'</div>';
			}
		}
		return $content;
	}

	function render() {
		$content = '';
		$this->getScript();
		try {
			$this->user->try2login();
			$contentPlus = $this->performAction($this->action);
			if ($contentPlus) {
				$content .= $contentPlus;
			} else if ($this->user->isAuth()) {
				$content .= $this->menuAction();
			} else if ($this->action == 'activate') {
				$content .= $this->activateActionReal();
			} else {
				$content .= $this->formAction();
				$content .= $this->registerAction();
			}
			$content = '<div id="AjaxLogin" '.($this->openable ? 'rel="toggle"' : '').'>'.$content.'</div>';
		} catch (Exception $e) {
			$content = '<div class="error">'.__($e->getMessage()).'</div>';
			if (DEVELOPMENT) {
				$content .= $e->getTraceAsString();
			}
		}
		return $content;
	}

	function getScript() {
		//$content = '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>';
		//$content = '<script src="http://code.jquery.com/jquery-1.8.1.min.js"></script>';
		IndexBE::getInstance()->addJQuery()
			->addJS('../../nadlib/js/jquery.form.js')
			->addJS('../../nadlib/js/ajaxLogin.js')
			->addCSS('../../nadlib/CSS/ajaxLogin.css');
	}

	function formAction(array $desc = NULL) {
		$f = new HTMLFormTable();
		$f->formMore = $this->formMore;
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getLoginDesc();
		}
		$f->showForm($desc);
		$f->hidden('action', 'login');
		$f->text('<div style="float: right"><a href="'.$_SERVER['PHP_SELF'].'?action=forgotPassword" rel="forgotPassword">'.__('Forgot Password').'</a></div>');
		$f->submit(__('Login'));
		$content = $f;
		//if (!$this->request->is_set('username')) {
		if ($this->withRegister) {
			$content = '<div id="loginForm">'.$content.'<hr><button class="buttonRegister">
			'.__('Register').'</button></div>';
		}
		return $content;
	}

	/**
	 * It's called "mode" for historical reasons, but it's good so that it's not overlapping with the possible "action"
	 * @return string
	 */
	function inlineFormAction() {
		if ($this->user && $this->user->isAuth()) {
			$content = '<form class="navbar-form pull-right" method="POST">
				'.$this->user->getName().' <a href="?c=Login&action=logout" class="ajax btn">'.__('Logout').'</a>
			</form>';
		} else {
			$content = '<form class="navbar-form pull-right" method="POST">
			<div class="form-group">
				<input type="hidden" name="c" value="'.get_class($this).'" />
				<input type="hidden" name="action" value="login" />

				<input class="form-control" type="text"
				name="username" placeholder="E-mail" value="'.$_REQUEST['username'].'" />
				<input class="form-control" type="password"
				name="password" placeholder="Password" />
				<button type="submit" class="btn btn-default">Sign in</button>
			</div>
			</form>';
		}
		return $content;
	}

	function getLoginDesc() {
		$desc = array();
		$desc['username']['label'] = __('E-mail');
		$desc['password']['label'] = __('Password');
		$desc['password']['type'] = 'password';
		return $desc;
	}

	/**
	 * Taken as a default from wagner-verlag login. Should most likely be overwritten in a subclass.
	 * @return string
	 */
	function loginAction() {
		$content = '';
		$username = $this->request->getTrim('username');
		$password = $this->request->getTrim('password');
		$passwordHash = md5($this->secret.$password);
		$check = $this->user->checkPassword($username, $passwordHash);
		if ($check) {
			$this->user->saveLogin($username, $passwordHash);
			$content .= '<div class="message">'.__('You are logged in.').'</div>';
			//$content .= '<script> document.location.reload(true); </script>';	// POST is a problem
			//$content .= '<script>document.location.replace(document.location);</script>';	// doesn't refresh at all
			$content .= '<script>
				//console.log(location.href);
				location.href = location.href.replace(location.hash, "") + (location.href.indexOf("?") != -1
					? "&"
					: "?"
				) + "reload=1" + location.hash
				//console.log(location.href);
			</script>';
			$content .= $this->menuAction();
			//header('Location: '.$_SERVER['REQUEST_URI']);
			//exit();
		} else {
			$content .= '<div class="error">'.__('Wrong login or password.').'</div>';
			$desc = $this->getLoginDesc();
			$desc['username']['value'] = $username;
			$desc['password']['cursor'] = true;
			$content .= $this->formAction($desc);
			$content .= $this->registerAction();
		}
		return $content;
	}

	function menuAction() {
		$content = '<div id="loginMenu">
			<a href="http://de.gravatar.com/" class="gravatar">
				<img src="'.$this->user->getGravatarURL(25).'" align="left" border="0">
			</a>'.
			$this->user->getName().'
			<br clear="all">
			<ul>
				<li><a href="'.$_SERVER['PHP_SELF'].'?action=profile" class="ajax">'.__('Edit Profile').'</a><div id="profileForm"></div></li>
				<li><a href="http://de.gravatar.com/" target="gravatar">'.__('Change Gravatar').'</a></li>
				<li><a href="'.$_SERVER['PHP_SELF'].'?action=password" class="ajax">'.__('Change Password').'</a><div id="passwordForm"></div></li>
				<li><a href="'.$_SERVER['PHP_SELF'].'?action=logout" class="ajax">'.__('Logout').'</a></li>
			</ul>
		</div>';
		return $content;
	}

	function profileAction(array $desc = NULL) {
		$f = new HTMLFormTable();
		$f->formMore = 'onsubmit="jQuery(this).ajaxSubmit({
			//function (res) { jQuery(\'#profileForm\').html(res); }
			target: \'#profileForm\',
			//url: \'buch.php\'
			}); return false;"';
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getProfileDesc();
			$desc = $f->fillValues($desc, $this->user->data);
		} // otherwise it comes from validate and contains the form input already
		//debug($desc);
		$f->prefix('profile');
		$f->showForm($desc);
		$f->prefix('');
		$f->hidden('action', 'saveProfile');
		$f->submit(__('Save'));
		$content = $f;
		return $content;
	}

	function getProfileDesc() {
		$desc = array();
		$desc['username']['label'] = __('E-mail');
		$desc['username']['validate'] = 'email';
		$desc['name']['label'] = __('Name');
		$desc['surname']['label'] = __('Surname');
		return $desc;
	}

	function saveProfileAction() {
		$content = '';
		$data = $this->request->getArray('profile');
		$desc = $this->getProfileDesc();
		$f = new HTMLFormTable();
		$desc = $f->fillValues($desc, $data);
		$val = new HTMLFormValidate($desc);
		$check = $val->validate();
		if ($check) {
			$this->user->update($data);
			$content .= '<div class="message">'.__('Profile updated.').'</div>';
			//mail($this->user->data[$this->user->loginField], __('Profile changed.'), 'Profile Changed', $this->mailHeaders);
		} else {
			$content .= $this->profileAction($val->getDesc());
		}
		return $content;
	}

	function passwordAction(array $desc = NULL) {
		$f = new HTMLFormTable();
		$f->formMore = 'onsubmit="jQuery(this).ajaxSubmit({
			//function (res) { jQuery(\'#passwordForm\').html(res); }
			target: \'#passwordForm\',
			//url: \'buch.php\'
			}); return false;"';
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getPasswordDesc();
		} // otherwise it comes from validate and contains the form input already
		//debug($desc);
		$f->showForm($desc);
		$f->hidden('action', 'savePassword');
		$f->submit(__('Change'));
		$content = $f;
		return $content;
	}

	function getPasswordDesc() {
		$desc = array();
		$desc['password']['label'] = __('Password');
		$desc['password']['type'] = 'password';
		return $desc;
	}

	function savePasswordAction() {
		$content = '';
		$password = $this->request->getTrim('password');
		$desc = $this->getPasswordDesc();
		$desc['password']['value'] = $password;
		$val = new HTMLFormValidate($desc);
		$check = $val->validate();
		if ($check) {
			$data = array(
				'password' => md5($this->secret.$password),
			);
			$this->user->update($data);
			$content .= '<div class="message">'.__('Password updated.').'</div>';
			$this->user->saveLogin($this->user->data[$this->user->loginField], $data['password']);
		} else {
			$content .= $this->passwordAction($val->getDesc());
		}
		return $content;
	}

	function logoutAction() {
		$this->user->logout();
		$content = '<div class="message">'.__('You are logged out.').'</div>';
		//$content .= '<script> document.location.reload(true); </script>';
		$content .= $this->formAction();
		$content .= $this->registerAction();
		return $content;
	}

	function forgotPasswordAction() {
		$content = '';
		$email = $this->request->getTrim('username');
		if ($email) {
			$this->user->findInDB(array($this->user->loginField => $email));
			if ($this->user->id) {
				$password = rand(1000000, 9999999);
				//debug($password);
				$this->user->update(array('password' => md5($this->secret.$password)));
				mail($this->user->data[$this->user->loginField],
					utf8_encode(__('New password generated')),
					utf8_encode(__('emailForgot', array(
					'%1' => $this->user->data['name'],
					'%2' => $this->user->data['surname'],
					'%3' => $password,
				))), $this->mailHeaders);
			}
			$content .= '<div class="message">'.__('If we have found this e-mail then we have sent you your new password.').'</div>';
		} else {
			$content .= '<div class="error">'.__('Enter you e-mail before asking for a new password.').'</div>';
		}
		$desc = $this->getLoginDesc();
		$desc['username']['value'] = $email;
		$desc['password']['cursor'] = true;
		$content .= $this->formAction($desc);
		$content .= $this->registerAction();
		return $content;
	}

	function registerAction(array $desc = NULL) {
		$f = new HTMLFormTable();
		$f->formMore = 'onsubmit="jQuery(this).ajaxSubmit({
			//function (res) { jQuery(\'#registerForm\').html(res); }
			target: \'#registerForm\',
			//url: \'buch.php\'
			}); return false;"';
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getRegisterDesc();
		} // otherwise it comes from validate and contains the form input already
		//debug($desc);
		$f->prefix('profile');
		$f->showForm($desc);
		$f->prefix('');
		$f->hidden('action', 'saveRegister');
		$f->submit(__('Register'));
		$content = $f;
		if (!$this->request->getTrim('profile')) {
			$content = '<div id="registerForm" style="display: none;">
				<a href="javascript:void(0);" class="backToLogin">'.
					__('Back to Login').
				'</a>'.$content.
			'</div>';
		}
		return $content;
	}

	function getRegisterDesc() {
		$desc = array();
		$desc['name']['label'] = __('Name');

		$desc['surname']['label'] = __('Surname');

		$desc['username']['label'] = __('E-mail');

		$desc['password']['label'] = __('Password');
		$desc['password']['type'] = 'password';

		return $desc;
	}

	function saveRegisterAction() {
		$content = '';
		$data = $this->request->getArray('profile');
		$desc = $this->getRegisterDesc();
		$f = new HTMLFormTable();
		$desc = $f->fillValues($desc, $data);
		$val = new HTMLFormValidate($desc);
		$check = $val->validate();
		if ($check) {
			$content .= $this->createUser($data);
		} else {
			$desc = $val->getDesc(); // with error message
			$content .= $this->registerAction($desc);
		}
		//unset($_SESSION['pageBeforeRegister']);
		$_SESSION['pageBeforeRegister'] = $_SERVER['HTTP_REFERER'];
		//debug($_SESSION['pageBeforeRegister']);
		return $content;
	}

	function createUser(array $data) {
		$data['password'] = md5($this->secret.$data['password']);
		$data['activated'] = 0;
		try {
			$this->user->insert($data);
		} catch (UserAlreadyExistsException $e) {
			// ok - continue
			$this->user->findInDB(array($this->user->loginField => $data['username']));
		}
		//debug($this->user->data);
		$activateURL = $this->getActivateURL();
		mail($this->user->data[$this->user->loginField],
			utf8_encode(__('Activate new account')),
			utf8_encode(__('emailActivation', array(
			'%1' => $this->user->data['name'],
			'%2' => $this->user->data['surname'],
			'%3' => $activateURL,
		))), $this->mailHeaders);
		$content = '<div class="message">'.__('You need to activate your account with the link sent to your e-mail address.').'</div>';
		return $content;
	}

	function getActivateURL() {
		//$activateURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&'.http_build_query($params);
		$params = array(
			'action' => 'activate',
			'id' => $this->user->id,
			'confirm' => md5($this->secret.serialize($this->user->id)),
		);
		$u = new URL();
		$u->appendParams($params);
		//$u->setComponent('path', 'buch.php');
		$activateURL = $u.'';
		$activateURL = str_replace('&form=1', '', $activateURL); // for anmeldung on warenkorb.php
		return $activateURL;
	}

	/**
	 *
	 * Why is it called differently and "Real"?
	 * Because in this case the confirmation message will appear before <html>
	 * as this is called from AJAX.
	 * activateActionReal() is called from render()
	 */
	function activateAction() {

	}

	function activateActionReal() {
		$content  = '';
		$id = $this->request->getTrim('id');
		$confirm = $this->request->getTrim('confirm');
		$this->user->findInDB(array('id' => $id));
		//debug($this->user->data);
		$confirm2 = md5($this->secret.serialize($this->user->id));
		//debug(array($id, $confirm, $confirm2));
		if ($confirm == $confirm2) {
			if ($this->user->data['activated']) {
				$content .= '<div class="message">'.__('Account already activated.').'</div>';
			} else {
				$this->user->update(array('activated' => 1));
				$content .= '<div class="message">'.__('Account activated.').'</div>';
				if (($redirect = $_SESSION['pageBeforeRegister'])) {
					//$content .= $redirect.'<br>';
					//header('Location: '.$redirect);
					$_SESSION['pageBeforeRegister_show'] = true;
					$content .= '<script> document.location = "'.htmlspecialchars($redirect).'"; </script>';
				}
			}
		} else {
			$content .= '<div class="error">'.__('Error activating your account.').'</div>';
		}
		$this->message = $content;
		$content = '';
		$content .= $this->formAction();
		$content .= $this->registerAction();
		return $content;
	}

	function getUser() {
		return $this->user;
	}

	function renderBigMessage() {
		echo '<big>'.$this->message.'</big>';
	}

}
