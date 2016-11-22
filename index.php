<?php

function __($a, $sub1 = NULL, $sub2 = NULL, $sub3 = NULL) {
	$a = str_replace('%1', $sub1, $a);
	$a = str_replace('%2', $sub2, $a);
	$a = str_replace('%3', $sub3, $a);
	return $a;
}

class NadlibIndex {

	/**
	 * @var Request
	 */
	var $request;

	function __construct() {
		if (file_exists('vendor/autoload.php')) {
			require_once 'vendor/autoload.php';
		} elseif (file_exists('../vendor/autoload.php')) {
			require_once '../vendor/autoload.php';
		} elseif (file_exists('../../vendor/autoload.php')) {
			require_once '../../vendor/autoload.php';
		} elseif (file_exists('../../../vendor/autoload.php')) {
			require_once '../../../vendor/autoload.php';
		}

		require_once 'init.php';
		$in = new InitNADLIB();
		$in->init();

		$this->dic = new DIContainer();
		$this->dic->index = function ($c) {
			require_once 'be/class/class.IndexBE.php';
			$indexBE = IndexBE::getInstance(true);
			return $indexBE;
		};
		$this->dic->debug = function ($c) {
			return new Debug($c->index);
		};
		$this->dic->config = function ($c) {
			return Config::getInstance();
		};
		$this->dic->autoload = function ($c) {
			return AutoLoad::getInstance();
		};

		if (!class_exists('Config')) {
			require_once 'be/class/class.ConfigBE.php';
			class_alias('ConfigBE', 'Config');
		}
		if (!class_exists('AppController', false)) {
//			class_alias('Controller', 'AppController');
			class_alias('AppController', 'AppControllerME');
		}
		if (!class_exists('Index')) {
//			class_alias('IndexBE', 'Index');
		}

		if (!file_exists('vendor/autoload.php')) {
			//throw new Exception('Run "composer update"');
		}
		$this->dic->config->defaultController = 'HomeBE';

	}

	function render() {
		if (Request::isCLI()) {
			$content[] = $this->cliMode();
		} else {
			chdir('be');
			//echo ($this->dic->autoload->nadlibFromCWD);
			//$this->dic->autoload->nadlibFromCWD = '../'.$this->dic->autoload->nadlibFromCWD;
			//echo ($this->dic->autoload->nadlibFromCWD);

			$i = $this->dic->index;
			/** @var $i IndexBE */
			//echo get_class($i), BR, class_exists('Index'), BR, get_class(Index::getInstance());
			$content[] = $i->render();
		}
		$content = $this->s($content);
		return $content;
	}

	function cliMode() {
		$content[] = 'Nadlib CLI mode';
		$this->request->importCLIparams();
		if ($cmd = $this->request->getTrim('0')) {
			$cmdAction = $cmd.'Action';
			if (method_exists($this, $cmdAction)) {
				$content[] = $this->$cmdAction();
			}
		} else {
			throw new InvalidArgumentException('"'.$cmd.'" is unknown');
		}
		return $content;
	}

	function initAction() {
		return 'initAction';
	}

	function s($content) {
		return MergedContent::mergeStringArrayRecursive($content);
	}

}

/** Should not be called $i because Index::getInstance() will return $GLOBALS['i'] */
$i2 = new NadlibIndex();
echo $i2->render();
