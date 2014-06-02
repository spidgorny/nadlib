<?php

require_once 'vendor/autoload.php';
require_once 'init.php';
//$in = new InitNADLIB();
//$in->init();
define('DEVELOPMENT', true);

function __($a) {
	return $a;
}

class NadlibIndex extends AppControllerBE {

	function __construct() {
		parent::__construct();
		if (!file_exists('vendor/autoload.php')) {
			throw new Exception('Run "composer update"');
		}
	}

	function render() {
		if (Request::isCLI()) {
			$content[] = $this->cliMode();
		} else {
			//$this->request->redirect('be/');
			//include('be/index.php');
			require_once 'be/class/class.IndexBE.php';	// force this Index class
			$i = Index::getInstance(true);
			$i->initController();
			$content[] = $i->render();
		}
		$content = IndexBase::mergeStringArrayRecursive($content);
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

}

$i = new NadlibIndex();
echo $i->render();
