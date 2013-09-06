<?php

class AutoLoad {

	/**
	 * @var array
	 */
	var $folders;

	/**
	 * @var bool
	 */
	var $useCookies = true;

	/**
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * @var AutoLoad
	 */
	private static $instance;

	protected $classFileMap = array();

	/**
	 * getFolders() is called from outside
	 * to be able to modify $useCookies
	 * #see register()
	 */
	protected function __construct() {
		//$this->folders = $this->getFolders();
		//debug($this->folders);
	}

	function getFolders() {
		require_once 'HTTP/class.Request.php';
		if (!Request::isCLI()) {
			if ($this->useCookies) {
				//debug('session_start');
				session_start();

				//unset($_SESSION[__CLASS__]['folders']);
				//debug($_SESSION[__CLASS__]);

				$folders = isset($_SESSION[__CLASS__]['folders']) ? $_SESSION[__CLASS__]['folders'] : array();
				$this->classFileMap = isset($_SESSION[__CLASS__]['classFileMap'])
					? $_SESSION[__CLASS__]['classFileMap']
					: array();
			}
		} else {
			$folders = array();
		}

		if (!$folders) {
			$this->loadConfig();
			//echo($configPath);
			if (class_exists('Config')) {
				$folders = Config::$includeFolders
					? array_merge(ConfigBase::$includeFolders, Config::$includeFolders)
					: ConfigBase::$includeFolders;
			} else {
				$folders = ConfigBase::$includeFolders;
			}
		}
		return $folders;
	}

	function loadConfig() {
		if (!class_exists('ConfigBase')) {
			require_once 'class.ConfigBase.php';
			$configPath = dirname($_SERVER['SCRIPT_FILENAME']).'/class/class.Config.php';
			if (file_exists($configPath)) {
				//echo($configPath);
				include_once $configPath;
			}
		}
	}

	function __destruct() {
		if ($this->useCookies) {
			$_SESSION[__CLASS__]['classFileMap'] = $this->classFileMap;
			$_SESSION[__CLASS__]['folders'] = $this->folders;
		}
	}

	function load($class) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);

		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);
		$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
		$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);	// Download

		$file = $this->classFileMap[$classFile];
		if ($file && file_exists($file)) {
			include_once $file;
		} else {
			$debug = $this->findInFolders($class, $subFolders);
		}

		if (!class_exists($classFile) && !interface_exists($classFile)) {
			unset($_SESSION[__CLASS__]['folders']);	// just in case
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

	function findInFolders($classFile, $subFolders) {
		$this->loadConfig();
		$appRoot = dirname($_SERVER['SCRIPT_FILENAME']);
		$appRoot = str_replace('/vendor/spidgorny/nadlib/be', '', $appRoot);
		foreach ($this->folders as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				$appRoot.DIRECTORY_SEPARATOR.
				$path.DIRECTORY_SEPARATOR.
				$subFolders.//DIRECTORY_SEPARATOR.
				'class.'.$classFile.'.php';
			if (file_exists($file)) {
				$debugLine = $classFile.' <span style="color: green;">'.$file.'</span><br />';
				include_once($file);
				$this->classFileMap[$classFile] = $file;
			} else {
				$debugLine = $classFile.' <span style="color: red;">'.$file.'</span><br />';
			}

			$debug[] = $debugLine;
			if ($this->debug) {
				echo $debugLine;
			}
			if (file_exists($file)) {
				break;
			}
		}
		return $debug;
	}

	/**
	 * @return AutoLoad
	 */
	static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	static function register() {
		$instance = self::getInstance();
		self::$instance->folders = self::$instance->getFolders();
		spl_autoload_register(array($instance, 'load'));
	}

}
