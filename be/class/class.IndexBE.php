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
		$c = get_class($this->controller);
		if ($c::$public || $this->user->isAuth()) {
			$content = parent::renderController();
		} else {
			throw new LoginException('Login first');
		}
		return $content;
	}

	function showSidebar() {
		$menu = array(
			'HomeBE' => 'Home',
			'ServerStat' => 'Server Stat',
			'ServerData' => 'Server Data',
			'Session' => 'Session',
			'ConfigView' => 'config.yaml',
			'Localize' => 'Localize',
			'PHPInfo' => 'phpinfo()',
			'Documentation' => 'Documentation',
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
