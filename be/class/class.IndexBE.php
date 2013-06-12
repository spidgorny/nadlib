<?php

class IndexBE extends IndexBase {

	public $projectName = 'nadlib|BE';

	function __construct() {
		parent::__construct();
		//debug_pre_print_backtrace();
		$this->addCSS('css/bootstrap.min.css');
		$this->addCSS('css/main.css');
		$this->addJQuery();
		$this->addJs('js/vendor/bootstrap.min.js');
		$this->user = new BEUser();
		Config::getInstance()->user = $this->user;	// for consistency
	}

	function renderController() {
		$c = get_class($this->controller);	/** @var $c Controller */
		//$public = $c::$public;	// Parse error:  syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM
		$vars = get_class_vars($c);
		$public = $vars['public'];
		if ($public || $this->user->isAuth()) {
			$content = parent::renderController();
		} else {
			throw new LoginException('Login first');
		}
		return $content;
	}

	function renderTemplate($content) {
		$v = new View('template.phtml', $this);
		$v->content = $content;
		$v->title = strip_tags($this->controller->title);
		$v->sidebar = $this->showSidebar();
		$lf = new LoginForm('inlineForm');	// too specific - in subclass
		$v->loginForm = $lf->dispatchAjax();
		$content = $v->render();	// not concatenate but replace
		return $content;
	}

	function showSidebar() {
		$menu = array(
			'HomeBE' => 'Home',
			'ServerStat' => 'Server Stat',
			'ServerData' => 'Server Data',
			'Session' => 'Session',
			'Cookies' => 'Cookies',
			'ConfigView' => 'config.yaml',
			'Localize' => 'Localize',
			'PHPInfo' => 'phpinfo()',
			'Documentation' => 'Documentation',
			'TestNadlib' => 'TestNadlib',
			'AlterDB' => 'Alter DB',
			'AlterCharset' => 'Alter Charset',
			'JumpFrontend' => '<- Frontend',
		);

		$c = Spyc::YAMLLoad('../../class/config.yaml');
		//debug($c['BEmenu']);
		if ($c['BEmenu']) {
			foreach($c['BEmenu'] as $key => $sub) {
				if (is_array($sub)) {
					$menu[$key] = new Recursive($key, $sub);
				} else {
					$menu[$key] = $sub;
				}
			}
		}

		$m = new Menu($menu);
		$m->recursive = true;
		$m->renderOnlyCurrent = false;
		return '<div class="well" style="padding: 8px 0;">'.$m.'</div>'.
			parent::showSidebar();
	}

}
