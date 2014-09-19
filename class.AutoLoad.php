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
	 * @var Path
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

	/**
	 * @var int
	 */
	public $count = 0;

	/**
	 * @var Path
	 */
	public $documentRoot;

	/**
	 * Relative to getcwd()
	 * Can be "../" from /nadlib/be/
	 * @var string
	 */
	public $nadlibRoot = 'vendor/spidgorny/nadlib/';

	/**
	 * Relative to $this->appRoot
	 * @var string
	 */
	public $nadlibFromDocRoot;

	public $nadlibFromCWD;

	/**
	 * @var Path
	 */
	public $componentsPath;

	/**
	 * getFolders() is called from outside
	 * to be able to modify $useCookies
	 * #see register()
	 */
	protected function __construct() {
		if (phpversion() < 5.3 && !defined('__DIR__')) {
			define('__DIR__', dirname(__FILE__));
		}
		require_once __DIR__ . '/HTTP/class.URL.php';
		require_once __DIR__ . '/HTTP/class.Request.php';
		require_once __DIR__ . '/HTTP/class.Path.php';
	}

	/**
	 * @return AutoLoad
	 */
	static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
			self::$instance->detectNadlibRoot();
			self::$instance->loadConfig();
			self::$instance->initFolders();
		}
		return self::$instance;
	}

	function detectNadlibRoot() {
		$this->documentRoot = new Path($_SERVER['DOCUMENT_ROOT']);
		$this->documentRoot->resolveLink();

		$scriptWithPath = URL::getScriptWithPath();
		$relToNadlibCLI = URL::getRelativePath($scriptWithPath, dirname(__FILE__));
		$relToNadlibPU = URL::getRelativePath(getcwd(), dirname(__FILE__));
		if (class_exists('Config')) {
			$config = Config::getInstance();
		}
		$this->nadlibRoot = dirname(__FILE__).'/';
		$this->appRoot = $this->detectAppRoot();
//		echo 'appRoot: ', $this->appRoot, BR;

		if ((strlen($this->appRoot) > 1) && !$this->appRoot->isAbsolute) { // '/', 'w:\\'
			$this->nadlibFromDocRoot = URL::getRelativePath($this->appRoot, realpath($this->nadlibRoot));
			$appRootIsRoot = true;
		} else {
			$path = new Path($scriptWithPath);
			//if (basename(dirname($scriptWithPath)) == 'nadlib') {
			if ($path->contains('nadlib')) {
				$this->nadlibFromDocRoot = Request::getDocumentRoot();
				$this->nadlibFromDocRoot = str_replace('/be', '', $this->nadlibFromDocRoot);
				$appRootIsRoot = 'DocumentRoot without /be';
			} else {
				$this->nadlibFromDocRoot = $relToNadlibPU;
				$appRootIsRoot = '$relToNadlibPU';
			}
		}
		$this->nadlibFromDocRoot = str_replace(dirname($_SERVER['SCRIPT_FILENAME']), '', $this->nadlibFromDocRoot);
		$this->nadlibFromDocRoot = cap($this->nadlibFromDocRoot, '/');
//		echo 'documentRoot: ', $this->documentRoot, BR;

		$this->nadlibFromCWD = URL::getRelativePath(getcwd(), $this->nadlibRoot);

		$this->componentsPath = new Path($this->appRoot);
		$this->componentsPath->setAsDir();
		if (!$this->componentsPath->appendIfExists('components')) {
			$this->componentsPath->up();
			if (!$this->componentsPath->appendIfExists('components')) {
				$this->componentsPath->up();
				if (!$this->componentsPath->appendIfExists('components')) {
					$this->componentsPath = new Path($this->documentRoot);
					if ($this->componentsPath->appendIfExists('components')) {	// no !
						//$this->componentsPath = $this->componentsPath->relativeFromDocRoot();	// to check exists()
					}
				}
			}
		}

		if (0) {
			echo '<pre>';
			print_r(array(
				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
				'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
				'getcwd()' => getcwd(),
				'__FILE__' => __FILE__,
				'$scriptWithPath' => $scriptWithPath,
				'dirname(__FILE__)' => dirname(__FILE__),
				'baseHref' => Request::getLocation(),
				'$relToNadlibCLI' => $relToNadlibCLI,
				'$relToNadlibPU' => $relToNadlibPU,
				'$this->nadlibRoot' => $this->nadlibRoot,
				'Config->documentRoot' => isset($config) ? $config->documentRoot : NULL,
				'$this->appRoot' => $this->appRoot.'',
				'appRootIsRoot' => $appRootIsRoot,
				'Config->appRoot' => isset($config) ? $config->appRoot : NULL,
				'$this->nadlibFromDocRoot' => $this->nadlibFromDocRoot,
				'$this->nadlibFromCWD' => $this->nadlibFromCWD,
				'request->getDocumentRoot()' => Request::getInstance()->getDocumentRoot(),
				'request->getLocation()' => Request::getInstance()->getLocation(),
				'this->componentsPath' => $this->componentsPath.'',
			));
			echo '</pre>';
		}
	}

	/**
	 * Original idea was to remove vendor/s/nadlib/be from the CWD
	 * but since $this->nadlibRoot is relative "../" it's impossible.
	 * Now we go back one folder until we find "class/class.Config.php" which MUST exist
	 *
	 * Since it's not 100% that it exists we just take the REQUEST_URL
	 */
	function detectAppRoot() {
		$appRoot = dirname(URL::getScriptWithPath());
		$appRoot = realpath($appRoot);
		//debug('$this->appRoot', $appRoot, $this->nadlibRoot);
		//$this->appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $this->appRoot);
		while ($appRoot && $appRoot != '/'
			&& !($appRoot{1} == ':' && strlen($appRoot) == 3)	// u:\
		) {
			$exists = file_exists($appRoot.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class.Config.php');
			//debug($appRoot, strlen($appRoot), $exists);
			if ($exists) {
				break;
			}
			$appRoot = dirname($appRoot);
		}

		if ($appRoot == '/') {  // nothing is found by previous method
			$appRoot = new Path(realpath(dirname(URL::getScriptWithPath())));
			$appRoot->upIf('nadlib');
			$appRoot->upIf('spidgorny');
			$appRoot->upIf('vendor');
		}

		// always add trailing slash!
	    $appRoot = cap($appRoot, '/');
		$appRoot = new Path($appRoot);
		return $appRoot;
	}

	function loadConfig() {
		nodebug(array(
			dirname($_SERVER['SCRIPT_FILENAME']),
			getcwd(),
		));
		if (!class_exists('ConfigBase')) {
			require_once 'class.ConfigBase.php';
		}
		if (!class_exists('Config')) {
			//$configPath = dirname(URL::getScriptWithPath()).'/class/class.Config.php';
			$configPath = $this->appRoot.'class'.DIRECTORY_SEPARATOR.'class.Config.php';
			//debug($configPath, file_exists($configPath)); exit();
			if (file_exists($configPath)) {
				include_once $configPath;
				//print('<div class="message">'.$configPath.' FOUND.</div>'.BR);
			} else {
				// some projects don't need Config
				//print('<div class="error">'.$configPath.' not found.</div>'.BR);
			}
		}
	}

	function initFolders() {
		//if (isset($_SESSION[__CLASS__])) unset($_SESSION[__CLASS__]);
		$this->folders = $this->getFolders();
		if (false) {
			print '<pre>';
			print_r($_SESSION[__CLASS__]);
			print_r($this->folders);
			print '</pre>';
		}
	}

	function getFolders() {
		require_once __DIR__ . '/HTTP/class.Request.php';
		$folders = array();
		if (!Request::isCLI()) {
			if ($this->useCookies) {
				//debug('session_start', $this->nadlibFromDocRoot);
				session_set_cookie_params(0, '');	// current folder
				if ((phpversion() > 5.4 && session_status() != PHP_SESSION_ACTIVE) && !headers_sent()) {
					session_start();
				}

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
			$folders = array();
			$folders = array_merge($folders, $this->getFoldersFromConfig());		// should come first to override /be/
			$folders = array_merge($folders, $this->getFoldersFromConfigBase());
		}

		return $folders;
	}

	function getFoldersFromConfig() {
		$folders = array();
		if (class_exists('Config') && Config::$includeFolders) {
			$folders = Config::$includeFolders;
			// append $this->appRoot before each
			foreach ($folders as &$el) {
				$el = $this->appRoot . $el;
			}
		} else {
			// that's ok. relax. be quiet.
			//echo ('Config not found');
		}
		return $folders;
	}

	function getFoldersFromConfigBase() {
		$folders = ConfigBase::$includeFolders;	// only ConfigBase here
		// append $this->nadlibRoot before each
		//if (basename(getcwd()) != 'be') {
			foreach ($folders as &$el) {
				$el = $this->nadlibRoot . $el;
			}
		/*} else {
			foreach ($folders as &$el) {
				$el = '../'. $el;
			}
			$folders[] = '../../../../class';	  // include Config from nadlib/be
			$folders[] = '../../../../model';	  // include User from nadlib/be
		}*/
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
		$this->count++;

		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);				// why?

		$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
		$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);	// Download

		$file = isset($this->classFileMap[$class]) ? $this->classFileMap[$class] : NULL;
		$file2 = str_replace('class.', '', $file);

		//echo $class.' ['.$file.'] '.(file_exists($file) ? "YES" : "NO").'<br />'."\n";

		if ($file && file_exists($file)) {
			/** @noinspection PhpIncludeInspection */
			include_once $file;
		} elseif ($file2 && file_exists($file2)) {
			/** @noinspection PhpIncludeInspection */
			include_once $file2;
		} else {
			$file = $this->findInFolders($classFile, $subFolders);
			if ($file) {
				include_once $file;
				$this->classFileMap[$class] = $file;
			}
		}

		if (!class_exists($class) && !interface_exists($class)) {
			if (isset($_SESSION)) {
				unset($_SESSION[__CLASS__]['folders']); // just in case
			}
			//debug($this->folders);
			if (false && class_exists('Config')) {
				$config = Config::getInstance();
				if ($config->config['autoload']['notFoundException']) {
					if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
					throw new Exception('Class '.$class.' ('.$file.') not found.');
				}
			} else {
				$this->log(__METHOD__.': '.$class.' not found');
			}
			//echo '<font color="red">'.$classFile.'-'.$file.'</font> ';
		} else {
			//echo $classFile.' ';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	/**
	 * @param $classFile
	 * @param $subFolders
	 * @return string
	 */
	function findInFolders($classFile, $subFolders) {
		$appRoot = class_exists('Config') ? Config::getInstance()->appRoot : '';
		foreach ($this->folders as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				//$this->nadlibRoot.
				$path.DIRECTORY_SEPARATOR.
				$subFolders.//DIRECTORY_SEPARATOR.
				'class.'.$classFile.'.php';

			// pre-check for file without "class." prefix
			if (!file_exists($file)) {
				$file2 = str_replace(DIRECTORY_SEPARATOR.'class.', DIRECTORY_SEPARATOR, $file);
				if (file_exists($file2)
					&& !(
						basename($file2) == 'Index.php'
						&& basename(dirname(realpath($file2))) == 'nadlib'
					)
				) {	// on windows exclude index.php
					$file = $file2;
				}
			}

			if (file_exists($file)) {
				$this->log($classFile.' <span style="color: green;">'.$file.'</span>: YES<br />'."\n");
				$this->log($classFile.' <span style="color: green;">'.$file2.'</span>: YES<br />'."\n");
				$this->classFileMap[$classFile] = $file;
				return $file;
			} else {
				$this->log($classFile.' <span style="color: red;">'.$file.'</span>: no<br />'."\n");
				$this->log($classFile.' <span style="color: red;">'.$file2.'</span>: no<br />'."\n");
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

	static function register() {
		$instance = self::getInstance();
		$result = spl_autoload_register(array($instance, 'load'), true, true);    // before composer
		if ($result) {
			//echo __METHOD__ . ' OK'.BR;
		} else {
			//debug(phpversion());
			//debug(error_get_last());
			//debug(is_callable(array($instance, 'load')));
			function __autoload($class) {
				$instance = AutoLoad::getInstance();
				$instance->load($class);
			}
		}
	}

}
