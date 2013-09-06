<?php

class IndexBE extends IndexBase {

	public $projectName = 'nadlib|BE';

	public $template = './../be/template/template.phtml';

	function __construct() {
		parent::__construct();
		//debug_pre_print_backtrace();
		$config = Config::getInstance();
		$config->defaultController = 'HomeBE';
		$config->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $config->documentRoot);

		$this->addCSS('components/bootstrap/css/bootstrap.min.css');
		$this->addCSS('vendor/spidgorny/nadlib/be/css/main.css');
		$this->addJQuery();
		$this->addJS('components/bootstrap/js/bootstrap.min.js');
		$this->user = new BEUser();
		$config->user = $this->user;	// for consistency
	}

	function renderController() {
		$c = get_class($this->controller);	/** @var $c Controller */
		//$public = $c::$public;	// Parse error:  syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM
		$vars = get_class_vars($c);
		$public = $vars['public'];
		if ($public || $this->user->isAuth()) {
			$content = parent::renderController();
		} else {
			//$this->message(new LoginForm());
			throw new LoginException('Login first');
		}
		return $content;
	}

	function renderTemplate($content) {
		$v = new View($this->template, $this);
		$v->content = $this->content . $content;
		$v->title = strip_tags($this->controller->title);
		$v->sidebar = $this->showSidebar();
		$lf = new LoginForm('inlineForm');	// too specific - in subclass
		$v->loginForm = $lf->dispatchAjax();
		$v->baseHref = $this->request->getLocation();
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
			'AlterIndex' => 'Alter Indexes',
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
