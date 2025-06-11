<?php

use spidgorny\nadlib\HTTP\URL;

class AjaxLogin extends AppControllerBE
{

	/**
	 * @var bool - true to be able to login when you're not yet logged-in
	 */
	public static $public = true;

	public $action;

	public $mailHeaders = "Content-type: text/plain; charset=\"UTF-8\"\r\nFrom: Wagner-Verlag <info@wagner-verlag.de>\r\n";

	public $openable = true;

	public $withRegister = true;

	/**
	 * Remove to disable jQuery dependency
	 * @var array<string, string>
	 */
	public $formMore = ['onsubmit' => "jQuery(this).ajaxSubmit({
			//function (res) { jQuery(\'#AjaxLogin\').html(res); }
			target: \'#AjaxLogin\',
			//url: \'buch.php\'
			}); return false;"];

	public $encloseTag = 'h3';

	/** @var UserModelInterface */
	public $user;

	/**
	 * Used for hashing password.
	 * Better override.
	 * @var string
	 */
	protected $secret = 'fdhgfjklgfdhj';

	/**
	 * Activation message saved to be shown on the center div
	 * @var string
	 */
	protected $message = '';

	protected $allowedActions = [
		'login',
		'forgotPassword',
		'saveRegister',
		'activate',
		'inlineForm',
		'logout'
	];

	public function __construct($action = null)
	{
		parent::__construct();
		$config = Config::getInstance();
		$config->mergeConfig($this);

		$action = $action ?: $this->request->getTrim('action');    // don't reverse this line as it will call mode=login twice
		if ($action) {
			$this->action = $action;
			//debug($this->action);
		}

		//$this->createDB();
	}

	public function createDB(): void
	{
		$this->db->perform("CREATE TABLE IF NOT EXISTS `user` (
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

	/**
	 * @return mixed[]
	 */
	public function dispatchAjax(): array
	{
		$content = [];
		if ($this->action) {
			$content[] = $this->performAction($this->detectAction());
			/*			if ($this->mode != 'activate') { // activate is NOT to be processed with AJAX
										header('Content-type: text/html; charset=ISO-8859-1');
										echo $content;
										exit();
									}
						*/
		} elseif ($_SESSION['pageBeforeRegister_show']) {
			$redirect = $_SESSION['pageBeforeRegister'];
			echo '<div class="absMessage">' . __('Account activated.') . '</div>';
			unset($_SESSION['pageBeforeRegister']);
		}

		return $content;
	}

	/**
	 * @return mixed[]
	 */
	public function performAction($action = null): array
	{
		$content = [];
		$action = $action ?: $this->action;
		if ($action) {
			if (in_array($action, $this->allowedActions) || $this->user->isAuth()) {
				try {
					$cb = $action . 'Action';
					$content[] = $this->$cb();
				} catch (Exception $e) {
					$content[] = '<div class="error alert alert-danger">' . __($e->getMessage()) . '</div>';
				}
			} else { // prevent profile editing for not logged in
				$content[] = '<div class="error alert alert-danger">' . __('Not logged in.') . '</div>';
			}
		}

		return $content;
	}

	/**
	 * $this->user->try2login() should been called already
	 */
	public function render(): array|string
	{
		$content = [];
		$this->getScript();
		try {
			$contentPlus = $this->performAction($this->action);
			if ($contentPlus !== []) {
				$content[] = $contentPlus;
			} elseif ($this->user && $this->user->isAuth()) {
				$content[] = $this->menuAction();
			} else {
				$content[] = $this->formAction();
				if ($this->withRegister) {
					$content[] = $this->registerAction();
				}
			}

			$content = ['<div id="AjaxLogin" ' . ($this->openable ? 'rel="toggle"' : '') . '>', $content, '</div>'];
		} catch (Exception $exception) {
			$content[] = '<div class="error_top alert alert-danger">' . __($exception->getMessage()) . '</div>';
			$content[] = $exception->getTraceAsString();
		}

		return $content;
	}

	public function getScript(): void
	{
		//$content = '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>';
		//$content = '<script src="http://code.jquery.com/jquery-1.8.1.min.js"></script>';
		$nadlibFromDocRoot = AutoLoad::getInstance()->nadlibFromDocRoot;
		Index::getInstance()->addJQuery()
			->addJS($nadlibFromDocRoot . 'js/jquery.form.js')
			->addJS($nadlibFromDocRoot . 'js/ajaxLogin.js')
			->addCSS($nadlibFromDocRoot . 'CSS/ajaxLogin.css');
	}

	public function menuAction(): string
	{
		$linkEdit = $this->getURL([
			'c' => get_class($this),
			'action' => 'profile',
		]);
		$linkPass = $this->getURL([
			'c' => get_class($this),
			'action' => 'password',
		]);
		$linkLogout = $this->getURL([
			'c' => get_class($this),
			'action' => 'logout',
		]);
		return '<div id="loginMenu">
			<a href="http://de.gravatar.com/" class="gravatar">
				<img src="' . $this->user->getGravatarURL(25) . '" align="left" border="0">
			</a>' .
			$this->user->getName() . '
			<br clear="all">
			<ul>
				<li><a href="' . $linkEdit . '" class="ajax">' . __('Edit Profile') . '</a><div id="profileForm"></div></li>
				<li><a href="http://de.gravatar.com/" target="gravatar">' . __('Change Gravatar') . '</a></li>
				<li><a href="' . $linkPass . '" class="ajax">' . __('Change Password') . '</a><div id="passwordForm"></div></li>
				<li><a href="' . $linkLogout . '" class="ajax">' . __('Logout') . '</a></li>
			</ul>
		</div>';
	}

	public function formAction(?array $desc = null)
	{
		$f = $this->getLoginForm($desc);
		return $this->encloseInAA($f, __('Login'));
	}

	/**
	 * Full screen - not for navbar
	 * @param array|NULL $desc
	 */
	public function getLoginForm(?array $desc = null): \HTMLFormTable
	{
		$f = new HTMLFormTable();
		$f->action('');     // specify action otherwise will logout
		$f->hidden('c', get_class($this));
		$f->formMore = $this->formMore;
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getLoginDesc();
		}

		$f->desc = $desc;
		$f->showForm();
		$f->hidden('action', 'login');
		$f->stdout .= $f->text('<div style="float: right">
			<a href="?action=forgotPassword" rel="forgotPassword">' . __('Forgot Password') . '</a>
		</div>');
		$f->stdout .= $f->submit(__('Login'), [
			'class' => 'btn btn-primary',
		]);
		return $f;
	}

	public function getLoginDesc(): array
	{
		return ['username' => [
			'label' => __('E-mail'),
			'class' => 'form-control',
			'placeholder' => 'E-mail address',
			'required' => '',
			'autofocus' => '',
		], 'password' => [
			'label' => __('Password'),
			'type' => 'password',
			'class' => 'form-control',
			'placeholder' => 'Password',
			'required' => '',
		]];
	}

	public function registerAction(?array $desc = null)
	{
		$f = new HTMLFormTable();
		$f->formMore['onsubmit'] = "jQuery(this).ajaxSubmit({
			//function (res) { jQuery('#registerForm').html(res); }
			target: '#registerForm',
			//url: 'buch.php'
			}); return false;";
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getRegisterDesc();
		} // otherwise it comes from validate and contains the form input already
		//debug($desc);
		$f->prefix('profile');
		$f->desc = $desc;
		$f->showForm();
		$f->prefix('');
		$f->hidden('action', 'saveRegister');
		$f->stdout .= $f->submit(__('Register'), [
			'class' => 'btn btn-secondary',
		]);
		$content = $this->encloseInAA($f, __('Register'));
		if (!$this->request->getTrim('profile')) {
			$content = '<div id="registerForm" _style="display: none;">
				<!--a href="javascript:void(0);" class="backToLogin">' .
				__('Back to Login') .
				'</a-->' . $content .
				'</div>';
		}

		return $content;
	}

	public function getRegisterDesc(): array
	{
		return ['email' => [
			'label' => __('E-mail'),
			'class' => 'form-control',
			'placeholder' => 'Your e-mail',
			'required' => '',
			'autofocus' => '',
		], 'name' => [
			'label' => __('Name'),
			'class' => 'form-control',
			'placeholder' => 'Your name',
			'required' => '',
		], 'surname' => [
			'label' => __('Surname'),
			'class' => 'form-control',
			'placeholder' => 'Your surname',
			'required' => '',
		], 'username' => [
			'label' => __('Username'),
			'class' => 'form-control',
			'placeholder' => 'Your username (nickname on this site)',
			'required' => '',
		], 'password' => [
			'label' => __('Password'),
			'type' => 'password',
			'class' => 'form-control',
			'placeholder' => 'Password',
			'required' => '',
		]];
	}

	/**
	 * It's called "mode" for historical reasons, but it's good so that it's not overlapping with the possible "action"
	 * Both Login and Password fields - not used
	 */
	public function inlineFormAction(): string
	{
		if ($this->user && $this->user->isAuth()) {
			$linkLogout = $this->getURL([
				'c' => get_class($this),
				'action' => 'logout',
			]);
			$content = '<form class="navbar-form navbar-right pull-right" method="POST">
			<div class="form-group">
				<p class="navbar-text" style="display: inline-block;">' . $this->user->getName() . '</p>
				<a href="' . $linkLogout . '" class="ajax btn btn-default">' . __('Logout') . '</a>
			</div>
			</form>';
		} else {
			$content = '<form class="navbar-form navbar-right row" method="POST">
				<input type="hidden" name="c" value="' . get_class($this) . '" />
				<input type="hidden" name="action" value="login" />
				<div class="form-group col-md-4">
					<input class="form-control"
						type="text"
						name="username"
						placeholder="E-mail"
						value="' . ($_REQUEST['username'] ?? null) . '" />
				</div>
				<div class="form-group col-md-4">
					<input class="form-control" type="password"
						name="password" placeholder="Password" />
				</div>
				<button type="submit" class="btn btn-success col-md-4">Sign in</button>
			</div>
			</form>';
		}

		return $content;
	}

	/**
	 * Taken as a default from wagner-verlag login. Should most likely be overwritten in a subclass.
	 * @return string[]
	 */
	public function loginAction(): array
	{
		$content = [];
		$username = $this->request->getTrim('username');
		$password = $this->request->getTrim('password');
		$passwordHash = md5($this->secret . $password);
//		$check = $this->user->checkPassword($username, $passwordHash);
		$check = 'todo';
		if ($check) {
//			$this->user->saveLogin($username, $passwordHash);
			$content[] = '<div class="message alert alert-success">' . __('You are logged in.') . '</div>';
			//$content[] = '<script> document.location.reload(true); </script>';	// POST is a problem
			//$content []= '<script>document.location.replace(document.location);</script>';	// doesn't refresh at all
			$content[] = '<script>
				//console.log(location.href);
				location.href = location.href.replace(location.hash, "") + (location.href.indexOf("?") != -1
					? "&"
					: "?"
				) + "reload=1" + location.hash
				//console.log(location.href);
			</script>';
			$content[] = $this->menuAction();
			//header('Location: '.$_SERVER['REQUEST_URI']);
			//exit();
		} else {
			$content[] = '<div class="error alert alert-danger">' . __('Wrong login or password.') . '</div>';
			$desc = $this->getLoginDesc();
			$desc['username']['value'] = $username;
			$desc['password']['cursor'] = true;
			$content[] = $this->formAction($desc);
			if ($this->withRegister) {
				$content[] = $this->registerAction();
			}
		}

		return $content;
	}

	/**
	 * @return array
	 */
	public function saveProfileAction(): array
	{
		$content = [];
		$data = $this->request->getArray('profile');
		$desc = $this->getProfileDesc();
		$f = new HTMLFormTable($desc);
		$desc = $f->fill($data);
		$val = new HTMLFormValidate($desc);
		$check = $val->validate();
		if ($check) {
//			$this->user->update($data);
			$content[] = '<div class="message alert alert-success">' . __('Profile updated.') . '</div>';
			//mail($this->user->data[$this->user->loginField], __('Profile changed.'), 'Profile Changed', $this->mailHeaders);
		} else {
			$content[] = $this->profileAction($val->getDesc());
		}

		return $content;
	}

	public function getProfileDesc(): array
	{
		$desc = [];
		$desc['username']['label'] = __('E-mail');
		$desc['username']['validate'] = 'email';
		$desc['name']['label'] = __('Name');
		$desc['surname']['label'] = __('Surname');
		return $desc;
	}

	public function profileAction(?array $desc = null): \HTMLFormTable
	{
		$f = new HTMLFormTable();
		$f->formMore['onsubmit'] = "jQuery(this).ajaxSubmit({
			//function (res) { jQuery('#profileForm').html(res); }
			target: '#profileForm',
			//url: 'buch.php'
			}); return false;";
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getProfileDesc();
			$f->desc = $desc;
			$desc = $f->fill($this->user->getData());
		} // otherwise it comes from validate and contains the form input already
		//debug($desc);
		$f->prefix('profile');
		$f->desc = $desc;
		$f->showForm();
		$f->prefix('');
		$f->hidden('action', 'saveProfile');
		$f->stdout .= $f->submit(__('Save'));

		return $f;
	}

	/**
	 * @return string[]
	 */
	public function savePasswordAction(): array
	{
		$content = [];
		$password = $this->request->getTrim('password');
		$desc = $this->getPasswordDesc();
		$desc['password']['value'] = $password;
		$form = new HTMLFormTable($desc);
		$val = new HTMLFormValidate($form);
		$check = $val->validate();
		if ($check) {
			$data = [
				'password' => md5($this->secret . $password),
			];
//			$this->user->update($data);
			$content[] = '<div class="message alert alert-success">' . __('Password updated.') . '</div>';
			$this->saveLogin($this->user->getLogin(), $data['password']);
		} else {
			$content[] = $this->passwordAction($val->getDesc());
		}

		return $content;
	}

	public function getPasswordDesc(): array
	{
		$desc = [];
		$desc['password']['label'] = __('Password');
		$desc['password']['type'] = 'password';
		return $desc;
	}

	public function saveLogin($login, $password)
	{
		// todo
	}

	public function passwordAction(?array $desc = null): \HTMLFormTable
	{
		$f = new HTMLFormTable();
		$f->formMore['onsubmit'] = "jQuery(this).ajaxSubmit({
			//function (res) { jQuery('#passwordForm').html(res); }
			target: '#passwordForm',
			//url: 'buch.php'
			}); return false;";
		$f->defaultBR = true;
		if (!$desc) {
			$desc = $this->getPasswordDesc();
		} // otherwise it comes from validate and contains the form input already
		//debug($desc);
		$f->desc = $desc;
		$f->showForm();
		$f->hidden('action', 'savePassword');
		$f->stdout .= $f->submit(__('Change'));
		return $f;
	}

	public function navbarLoginForm(): void
	{
		throw new RuntimeException('Not implemented');
//		return '<a href="' . LoginService::class . '" class="btn btn-primary navbar-btn">Login</a>';
	}

	public function logoutForm(): \HTMLTag
	{
		//		$content = '
//			<div class="navbar-form">'.$a.'</div>
//		';
		return new HTMLTag('a', [
			'href' => get_class($this) . '?action=logout',
			'class' => 'btn btn-default',
		], __('Logout'));
	}

	public function logoutAction()
	{
		$this->logout();
		$content[] = '<div class="message alert alert-success">' . __('You are logged out.') . '</div>';
		//$content[] = '<script> document.location.reload(true); </script>';
		$content[] = $this->formAction();
		if ($this->withRegister) {
			$content[] = $this->registerAction();
		}

		return $content;
	}

	public function logout()
	{
		// @todo
	}

	/**
	 * @return mixed[]
	 */
	public function saveRegisterAction(): array
	{
		$content = [];
		$data = $this->request->getArray('profile');
		$desc = $this->getRegisterDesc();
		$f = new HTMLFormTable($desc);
		$desc = $f->fill($data);
		$form = new HTMLFormTable($desc);
		$val = new HTMLFormValidate($form);
		$check = $val->validate();
		if ($check) {
			$content[] = $this->createUser($data);
		} else {
			$desc = $val->getDesc(); // with error message
			$content[] = $this->registerAction($desc);
		}

		//unset($_SESSION['pageBeforeRegister']);
		$_SESSION['pageBeforeRegister'] = $_SERVER['HTTP_REFERER'];
		//debug($_SESSION['pageBeforeRegister']);
		return $content;
	}

	public function createUser(array $data): string
	{
		$data['password'] = md5($this->secret . $data['password']);
		$data['activated'] = 0;
		try {
			$this->user->insert($data);
		} catch (UserAlreadyExistsException $userAlreadyExistsException) {
			// ok - continue
//			$this->user->findInDB([$this->user->loginField => $data['username']]);
		}

		return '<div class="message alert alert-info">' . __('You need to activate your account with the link sent to your e-mail address.') . '</div>';
	}

	public function getActivateURL(): string
	{
		//$activateURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&'.http_build_query($params);
		$params = [
			'action' => 'activate',
			'id' => $this->user->getID(),
			'confirm' => md5($this->secret . serialize($this->user->getID())),
		];
		$u = new URL();
		$u->appendParams($params);
		//$u->setComponent('path', 'buch.php');
		$activateURL = $u . ''; // for anmeldung on warenkorb.php
		return str_replace('&form=1', '', $activateURL);
	}

	/**
	 *
	 * Why is it called differently and "Real"?
	 * Because in this case the confirmation message will appear before <html>
	 * as this is called from AJAX.
	 * activateActionReal() is called from render()
	 */
	public function activateAction()
	{

	}

	public function getUser()
	{
		return $this->user;
	}

	public function renderBigMessage(): void
	{
		echo '<big>' . $this->message . '</big>';
	}

}
