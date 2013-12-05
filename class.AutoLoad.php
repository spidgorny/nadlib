<?php

class AutoLoad {

	/**
	 * @var array
	 */
	var $folders = array();

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

	/**
	 * @var string
	 */
	public $appRoot;

	/**
	 * Session stored map of each class to a file.
	 * This prevents searching for each file.
	 * @var array
	 */
	protected $classFileMap = array();

	/**
	 * @var Config
	 */
	public $config;

	public $nadlibRoot = 'vendor/spidgorny/nadlib/';

	/**
	 * getFolders() is called from outside
	 * to be able to modify $useCookies
	 * #see register()
	 */
	protected function __construct() {
		//$this->folders = $this->getFolders();
		//debug($this->folders);
		require_once 'class.ConfigBase.php';

		$this->appRoot = dirname($_SERVER['SCRIPT_FILENAME']);
		$this->appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $this->appRoot);

		$configPath = $this->appRoot.'/class/class.Config.php';	// config from the main project
		if (file_exists($configPath)) {
			//echo($configPath);
			include_once $configPath;
			//$this->config = Config::getInstance();	// autoload!
		}
		//echo($configPath);
	}

	function initFolders() {
		$this->folders = $this->getFolders();
		//print '<pre>';
		//print_r($this->folders);
		//exit;
	}

	function getFolders() {
		require_once __DIR__.'/HTTP/class.Request.php';
		$folders = array();
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
		}

		if (!$folders) {
			$this->loadConfig();
			$folders = ConfigBase::$includeFolders;
			foreach ($folders as &$el) {
				$el = $this->nadlibRoot . $el;
			}
			if (class_exists('Config') && Config::$includeFolders) {
				$folders = array_merge($folders, Config::$includeFolders);
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
		$classFile = end($namespaces);				// why?

		$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
		$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);	// Download

		$file = $this->classFileMap[$class];
		if ($file && file_exists($file)) {
			include_once $file;
		} else {
			$debug = $this->findInFolders($classFile, $subFolders);
			$this->classFileMap[$class] = $file;
		}

		if (!class_exists($class) && !interface_exists($class)) {
			unset($_SESSION[__CLASS__]['folders']);	// just in case
			//debug($this->folders);
			if (class_exists('Config')) {
				$config = Config::getInstance();
				if ($config->config['autoload']['notFoundException']) {
					debug($debug);
					throw new Exception('Class '.$class.' ('.$file.') not found.');
				}
			}
			//echo '<font color="red">'.$classFile.'-'.$file.'</font> ';
		} else {
			//echo $classFile.' ';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function findInFolders($classFile, $subFolders) {
		$this->loadConfig();
		$appRoot = Config::getInstance()->appRoot;
		printbr($appRoot);
		foreach ($this->folders as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				$appRoot.DIRECTORY_SEPARATOR.
				$path.DIRECTORY_SEPARATOR.
				$subFolders.//DIRECTORY_SEPARATOR.
				'class.'.$classFile.'.php';

			// pre-check for file without "class." prefix
			if (!file_exists($file)) {
				$file2 = str_replace('/class.', '/', $file);
				if (file_exists($file2)) {
					$file = $file2;
				}
			}

			if (file_exists($file)) {
				$debugLine = $classFile.' <span style="color: green;">'.$file.'</span><br />'."\n";
				include_once($file);
				$this->classFileMap[$classFile] = $file;
			} else {
				$debugLine = $classFile.' <span style="color: red;">'.$file.'</span><br />'."\n";
			}

			$debug[] = $debugLine;
			if ($this->debug && $_COOKIE['debug']) {
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
		$instance->initFolders();
		spl_autoload_register(array($instance, 'load'));
	}

}
