<?php

class AutoLoad {

	/**
	 * @var array
	 */
	var $folders;

	function __construct() {
		$this->folders = $this->getFolders();
		//debug($this->folders);
	}

	function getFolders() {
		require_once 'HTTP/class.Request.php';
		if (!Request::isCLI()) {
			session_start();
			//unset($_SESSION['autoloadCache']);
			$folders = isset($_SESSION['autoloadCache']) ? $_SESSION['autoloadCache'] : NULL;
		} else {
			$folders = array();
		}

		if (!$folders) {
			require_once 'class.ConfigBase.php';
			if (file_exists($configPath = dirname($_SERVER['SCRIPT_FILENAME']).'/class/class.Config.php')) {
				//echo($configPath);
				include_once $configPath;
			}
			//echo($configPath);
			if (class_exists('Config')) {
				$folders = Config::$includeFolders
					? array_merge(ConfigBase::$includeFolders, Config::$includeFolders)
					: ConfigBase::$includeFolders;
			} else {
				$folders = ConfigBase::$includeFolders;
			}
			$_SESSION['autoloadCache'] = $folders;
		}
		return $folders;
	}

	function load($class) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($class == 'IndexBE') {
			//debug_pre_print_backtrace();
		}
		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);
		$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
		$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);	// Download
		foreach ($this->folders as $path) {
			$file = dirname(__FILE__).DIRECTORY_SEPARATOR.
				$path.DIRECTORY_SEPARATOR.
				$subFolders.DIRECTORY_SEPARATOR.
				'class.'.$classFile.'.php';
			if (file_exists($file)) {
				$debug[] = $class.' <span style="color: green;">'.$file.'</span><br />';
				include_once($file);
				break;
			} else {
				$debug[] = $class.' <span style="color: red;">'.$file.'</span>: '.file_exists($file).'<br />';
			}
		}
		if (!class_exists($classFile) && !interface_exists($classFile)) {
			unset($_SESSION['autoloadCache']);	// just in case
			//debug($this->folders);
			if (class_exists('Config')) {
				$config = Config::getInstance();
				if ($config->config['autoload']['notFoundException']) {
					debug($debug);
					throw new Exception('Class '.$class.' ('.$file.') not found.');
				}
			}
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	static function register() {
		static $instance;
		if (!$instance) $instance = new self();
		spl_autoload_register(array($instance, 'load'));
	}

}
