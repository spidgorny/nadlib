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
		);

		$c = Spyc::YAMLLoad('class/config.yaml');
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
