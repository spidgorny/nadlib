<?php

class Index extends IndexBase {

	public $projectName = 'nadlib|BE';

	public $template = './../be/template/template.phtml';

	/**
	 * @var array
	 */
	public $menu;

	function __construct() {
		parent::__construct();
		//debug_pre_print_backtrace();
		$config = Config::getInstance();
		$config->defaultController = 'HomeBE';
		$config->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $config->documentRoot);
		$c = Config::getInstance();
		// it's not reading the config.yaml from /be/, but from the project root
		$c->config['View']['folder'] = '../be/template/';

		//$c->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $c->documentRoot);	// for CSS
		//Config::getInstance()->documentRoot .= '/vendor/spidgorny/nadlib/be';
		//base href will be fixed manually below

		$c->appRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $c->appRoot);

		$this->addCSS('components/bootstrap/css/bootstrap.min.css');
		$this->addCSS('vendor/spidgorny/nadlib/be/css/main.css');
		$this->addCSS('vendor/spidgorny/nadlib/CSS/TaylorProfiler.css');
		$this->addJQuery();
		$this->addJS('components/bootstrap/js/bootstrap.min.js');
		$this->user = new BEUser();
		$this->user->id = 'nadlib';
		$this->user->try2login();
		$c->user = $this->user;	// for consistency

		$this->ll = new LocalLangDummy();
		//debug($this->ll);

		$this->menu = array(
			'HomeBE' => 'Home',
			'ServerStat' => new Recursive('Info', array(
					'ServerStat' => 'Server Stat',
					'ServerData' => 'Server Data',
					'Session' => 'Session',
					'Cookies' => 'Cookies',
					'ConfigView' => 'config.yaml',
					'PHPInfo' => 'phpinfo()',
					'Documentation' => 'Documentation',
				)),
			'TestNadlib' => new Recursive('Test', array(
					'TestNadlib' => 'TestNadlib',
					'ValidatorCheck' => 'Validator Check',
					'UnitTestReport' => 'Unit Test Report',
				)),
			'ExplainQuery' => new Recursive('DB', array(
					'AlterDB' => 'Alter DB',
					'AlterCharset' => 'Alter Charset',
					'AlterTable' => 'Alter Table',
					'AlterIndex' => 'Alter Indexes',
					'OptimizeDB' => 'Optimize DB',
					'ExplainQuery' => 'Explain Query',
					'Localize' => 'Localize',
				)),
			'ClearCache' => 'Clear Cache',
			'JumpFrontend' => '<- Frontend',
		);
	}

	function renderController() {
		$c = get_class($this->controller);	/** @var $c Controller */
		//$public = $c::$public;	// Parse error:  syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM
		$vars = get_class_vars($c);
		$public = $vars['public'];
		if ($public || $this->user->isAuth()) {
			$this->controller->user = $this->user;	// BEUser instead of grUser
			$content = parent::renderController();
		} else {
			$this->error('Accessing this page requires a valid login');
			$loginForm = new LoginForm();
			$loginForm->withRegister = false;
			$content = $loginForm->layout->wrap(
				$this->content.
				$loginForm->render()
			);
			$this->content = '';
			/*throw new LoginException('
				Login first <a href="vendor/spidgorny/nadlib/be/">here</a>');
			*/
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
		// is the root of the project
		$v->baseHref = $this->request->getLocation();
		//$v->baseHref = str_replace('/vendor/spidgorny/nadlib/be', '', $v->baseHref);	// for CSS
		$content = $v->render();	// not concatenate but replace
		return $content;
	}

	function showSidebar() {
		$c = Spyc::YAMLLoad('../../../../class/config.yaml');
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

		$m = new Menu($this->menu);
		$m->recursive = false;
		$m->level = 1;
		$m->renderOnlyCurrent = true;
		$m->useControllerSlug = false;
		$m->setCurrent($m->level);
		$m->setBasePath();
		//$m->basePath->setPath('vendor/spidgorny/nadlib/be/');
		//debug($m);
		return '<div class="_well" style="padding: 0;">'.$m.'</div>'.
			parent::showSidebar();
	}

}
