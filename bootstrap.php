<?php

class Bootstrap {

	function boot() {
		debug_print_backtrace();
		echo 'cwd: ', basename(getcwd()), "\n";
		if (basename(getcwd()) == 'tests') {
		}

		require_once __DIR__ . '/init.php';
		@define('BR', "\n");

		// first in order to load phpunit classes
		@define('DS', DIRECTORY_SEPARATOR);
		$globalAutoload = getenv('USERPROFILE') . DS . 'AppData' . DS . 'Roaming' . DS . 'Composer' . DS . 'vendor' . DS . 'autoload.php';
		echo $globalAutoload, BR;
		/** @noinspection PhpIncludeInspection */
		require_once $globalAutoload;

		require_once __DIR__ . '/ConfigBase.php';
		//require_once 'TestConfig.php';
		//class_alias('TestConfig', 'Config');

		$_COOKIE['debug'] = 1;

		require_once __DIR__ . '/InitNADLIB.php';
		$n = new InitNADLIB();
		$n->init();

		$al = AutoLoad::getInstance();
		$al->addFolder(__DIR__);

		//$this->loadVendorAutoload();

		//debug(spl_autoload_functions());

		echo 'bootstrap.php done', BR;
	}

	public function loadVendorAutoload() {
		$path = trimExplode('/', str_replace('\\', '/', getcwd()));
		//debug($path);
		foreach (range(sizeof($path), 0, -1) as $i) {
			$dir = implode(DS, array_slice($path, 0, $i));
			$autoloadPHP = $dir . '/vendor/autoload.php';
			echo $autoloadPHP, BR;
			if (file_exists($autoloadPHP)) {
				/** @noinspection PhpIncludeInspection */
				require_once $autoloadPHP;
				echo $autoloadPHP, BR;
				break;
			}
		}
	}

}

// run this after bootstrapping
//Config::getInstance()->postInit();

(new Bootstrap())->boot();
