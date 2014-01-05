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
		require_once __DIR__.'/HTTP/class.URL.php';
		require_once __DIR__.'/HTTP/class.Request.php';
		$scriptWithPath = URL::getScriptWithPath();

		// for CLI
		$relToNadlib = URL::getRelativePath($scriptWithPath, dirname(__FILE__));

		// for PHPUnit
		$relToNadlib = URL::getRelativePath(getcwd(), dirname(__FILE__));
		$this->nadlibRoot = $relToNadlib;
		if (false) {
			echo '<pre>';
			print_r(array(
				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
				'getcwd()' => getcwd(),
				'__FILE__' => __FILE__,
				'$scriptWithPath' => $scriptWithPath,
				'dirname(__FILE__)' => dirname(__FILE__),
				'$relToNadlib' => $relToNadlib,
				'$this->nadlibRoot' => $this->nadlibRoot,
			));
			//print_r($_SERVER);
			echo '</pre>';
		}

		$this->appRoot = dirname(URL::getScriptWithPath());
		$this->appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $this->appRoot);
		//debug('$this->appRoot', $this->appRoot);

		$this->loadConfig();
	}

	function loadConfig() {
		nodebug(array(
			dirname($_SERVER['SCRIPT_FILENAME']),
			getcwd(),
		));
		if (!class_exists('ConfigBase')) {
			require_once 'class.ConfigBase.php';
			//$configPath = dirname(URL::getScriptWithPath()).'/class/class.Config.php';
			$configPath = getcwd().'/class/class.Config.php';
			//debug($configPath, file_exists($configPath));
			if (file_exists($configPath)) {
				include_once $configPath;
			} else {
				print('class.Config.php not found.<br />'."\n");
			}
		}
	}

	function initFolders() {
		$this->folders = $this->getFolders();
		if (false) {
			print '<pre>';
			print_r($_SESSION[__CLASS__]);
			print_r($this->folders);
			print '</pre>';
		}
	}

	function getFolders() {
		require_once __DIR__.'/HTTP/class.Request.php';
		$folders = array();
		if (!Request::isCLI()) {
			if ($this->useCookies) {
				//debug('session_start');
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
			$folders = ConfigBase::$includeFolders;	// only ConfigBase here
			foreach ($folders as &$el) {
				$el = $this->nadlibRoot . $el;
			}
			if (class_exists('Config') && Config::$includeFolders) {
				//d($folders, Config::$includeFolders);
				$folders = array_merge($folders, Config::$includeFolders);
			}
		}

		return $folders;
	}

	function __destruct() {
		if ($this->useCookies) {
			$_SESSION[__CLASS__]['classFileMap'] = $this->classFileMap;
			$_SESSION[__CLASS__]['folders'] = $this->folders;
		}
	}

	/**
	 * Main __autoload() function
	 * @param $class
	 * @throws Exception
	 */
	function load($class) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);

		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);				// why?

		$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
		$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);	// Download

		$file = $this->classFileMap[$class];

		//echo $class.' ['.$file.'] '.(file_exists($file) ? "YES" : "NO").'<br />'."\n";

		if ($file && file_exists($file)) {
			include_once $file;
		} else {
			$file = $this->findInFolders($classFile, $subFolders);
			if ($file) {
				include_once $file;
				$this->classFileMap[$class] = $file;
			}
		}

		if (!class_exists($class) && !interface_exists($class)) {
			unset($_SESSION[__CLASS__]['folders']);	// just in case
			//debug($this->folders);
			if (false && class_exists('Config')) {
				$config = Config::getInstance();
				if ($config->config['autoload']['notFoundException']) {
					if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
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
		foreach ($this->folders as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				$this->appRoot.DIRECTORY_SEPARATOR.
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
				$this->log($classFile.' <span style="color: green;">'.$file.'</span>: YES<br />'."\n");
				$this->classFileMap[$classFile] = $file;
				return $file;
			} else {
				$this->log($classFile.' <span style="color: red;">'.$file.'</span>: no<br />'."\n");
			}
		}
	}

	function log($debugLine) {
		if ($this->debug && $_COOKIE['debug']) {
			if (Request::isCLI()) {
				echo strip_tags($debugLine);
			} else {
				echo $debugLine;
			}
		}
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
