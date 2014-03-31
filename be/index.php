<?php

ini_set('display_errors', true);
error_reporting(E_ALL);
require_once '../vendor/autoload.php';
//require_once '../../../../vendor/autoload.php';
require_once '../init.php';

require_once dirname(__FILE__) . '/../class.AutoLoad.php';

class AutoLoadBE extends AutoLoad {

	/**
	 * @var AutoLoad
	 */
	private static $instance2;

	function getFolders() {
		require_once __DIR__ . '/HTTP/class.Request.php';
		$folders = array();
		if (!Request::isCLI()) {
			if ($this->useCookies) {
				//debug('session_start', $this->nadlibFromDocRoot);
				session_set_cookie_params(0, '');	// current folder
				session_start();

				if (isset($_SESSION[__CLASS__])) {
					$folders = isset($_SESSION[__CLASS__]['folders'])
						? $_SESSION[__CLASS__]['folders']
						: array();
					$this->classFileMap = isset($_SESSION[__CLASS__]['classFileMap'])
						? $_SESSION[__CLASS__]['classFileMap']
						: array();
				}
			}
		}

		if (!$folders) {
			$folders = array('be/class');
			$folders = array_merge($folders, $this->getFoldersFromConfigBase());
			$folders = array_merge($folders, $this->getFoldersFromConfig());		// should come first to override /be/
		}
		debug($folders);

		return $folders;
	}

	/**
	 * @return AutoLoad
	 */
	static function getInstance() {
		if (!self::$instance2) {
			self::$instance2 = new self();
		}
		return self::$instance2;
	}

}

require_once '../Controller/class.IndexBase.php';	// force this Index class
require_once 'class/class.IndexBE.php';	            // force this Index class
$n = new InitNADLIB();
$n->al = AutoLoadBE::getInstance();
$n->al->debug = true;
$n->init();

$i = Index::getInstance(true);
$i->initController();
echo $i->render();
AutoLoad::getInstance()->__destruct();
