<?php

class IndexBE extends IndexBase
{

	public $projectName = 'nadlib|BE';

	public $template = './../be/template/template.phtml';

	/**
	 * @var Menu
	 */
	public $menu;

	function __construct()
	{
		parent::__construct();
		//debug_pre_print_backtrace();
		$config = Config::getInstance();
		$config->defaultController = 'HomeBE';
		$config->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $config->documentRoot);
		$config->documentRoot = str_replace('/nadlib/be', '', $config->documentRoot);
		//$config->documentRoot = $config->documentRoot ?: '/';	// must end without slash
		// it's not reading the config.yaml from /be/, but from the project root
		$config->config['View']['folder'] = '../be/template/';

		//$c->documentRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $c->documentRoot);	// for CSS
		//Config::getInstance()->documentRoot .= '/vendor/spidgorny/nadlib/be';
		//base href will be fixed manually below

		$config->appRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $config->appRoot);
		$config->appRoot = str_replace('/nadlib/be', '', $config->appRoot);

		$this->nadlibFromDocRoot = AutoLoad::getInstance()->nadlibFromDocRoot;

		$this->header['modernizr.js'] = '<script src="' . $this->nadlibFromDocRoot . 'components/modernizr/modernizr.js"></script>';
		$this->addCSS($this->nadlibFromDocRoot . 'components/bootstrap/css/bootstrap.min.css');
		$this->addCSS($this->nadlibFromDocRoot . 'be/css/main.css');
		$this->addCSS($this->nadlibFromDocRoot . 'CSS/TaylorProfiler.css');
		$this->addJQuery();
		$this->addJS($this->nadlibFromDocRoot . 'components/bootstrap/js/bootstrap.min.js');
		$this->user = new BEUser();
		$this->user->id = 'nadlib';
		$this->user->try2login();
		$config->user = $this->user;    // for consistency

		$this->ll = new LocalLangDummy();
		//debug($this->ll);

		$menu = array(
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
			'UnitTestReport' => new Recursive('Test', array(
				'UnitTestReport' => 'Unit Test Report',
				'ValidatorCheck' => 'Validator Check',
				'TestQueue' => 'Test Queue',
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
			'ClearCache' => new Recursive('FE', array(
				'ClearCache' => 'Clear Cache',
				'JumpFrontend' => '<- Frontend',
			)),
		);
		$menu = $this->loadBEmenu($menu);
		$this->menu = new Menu($menu, 0);
		$this->menu->ulClass = 'nav navbar-nav';
		$this->menu->liClass = '';
		$this->menu->recursive = false;
		$this->menu->renderOnlyCurrent = true;
		$this->menu->useControllerSlug = false;
		$this->menu->setBasePath();    // because 1und1 rewrite is not enabled
		//debug($this->menu->basePath);
		$docRoot = $this->request->getDocumentRoot();
		$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot . 'be', '', $docRoot);    // remove vendor/spidgorny/nadlib/be
		$this->menu->basePath->setPath($docRoot . $this->nadlibFromDocRoot . 'be/');
	}

	function loadBEmenu(array $menu)
	{
		if (file_exists('class/config.yaml')) {
			$c = Spyc::YAMLLoad('../../../../class/config.yaml');
			//debug($c['BEmenu']);
			if ($c['BEmenu']) {
				//$c['BEmenu'] = array('FE' => $c['BEmenu']);
				foreach ($c['BEmenu'] as $key => $sub) {
					if (is_array($sub)) {
						$menu['ClearCache']->elements[$key] = new Recursive($key, $sub);
					} else {
						$menu['ClearCache']->elements[$key] = $sub;
					}
				}
			}
		}


		return $menu;
	}

	function renderController()
	{
		$c = get_class($this->controller);
		/** @var $c Controller */
		//$public = $c::$public;	// Parse error:  syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM
		$vars = get_class_vars($c);
		$public = $vars['public'];
		if ($public || $this->user->isAuth()) {
			$this->controller->user = $this->user;    // BEUser instead of grUser
			$content = parent::renderController();
		} else {
			$this->error('Accessing this page requires a valid login');
			$loginForm = new LoginForm();
			$loginForm->withRegister = false;
			$content = $loginForm->layout->wrap(
				$this->content .
				$loginForm->render()
			);
			$this->content = '';
			/*throw new LoginException('
				Login first <a href="vendor/spidgorny/nadlib/be/">here</a>');
			*/
		}
		return $content;
	}

	function renderTemplate($content)
	{
		$v = new View($this->template, $this);
		$v->content = $this->content . $content;
		$v->title = strip_tags($this->controller->title);
		$v->sidebar = $this->showSidebar();
		$lf = new LoginForm('inlineForm');    // too specific - in subclass
		$v->loginForm = $lf->dispatchAjax();
		// is the root of the project
		$v->baseHref = $this->request->getLocation();
		//$v->baseHref = str_replace('/vendor/spidgorny/nadlib/be', '', $v->baseHref);	// for CSS
		$content = $v->render();    // not concatenate but replace
		return $content;
	}

	function showSidebar()
	{
		$m = new Menu($this->menu->items->getData(), 1);
		$m->recursive = false;
		$m->renderOnlyCurrent = true;
		$m->useControllerSlug = false;
		//$m->useRecursiveURL = false;
		$m->setBasePath();    // because 1und1 rewrite is not enabled
		$docRoot = $m->request->getDocumentRoot();
		$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot . 'be', '', $docRoot);    // remove vendor/spidgorny/nadlib/be
		$m->basePath->setPath($docRoot . $this->nadlibFromDocRoot . 'be/');
		//debug($m);
		return '<div class="_well" style="padding: 0;">' . $m . '</div>' .
			parent::showSidebar();
	}

}
