<?php

class AutoLoad {

	/**
	 * @var AutoLoadFolders
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

	/**
	 * @var Path
	 */
	public $appRoot;

	/**
	 * Session stored map of each class to a file.
	 * This prevents searching for each file.
	 * @var array
	 */
	public $classFileMap = array();

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

	public $stat = array(
		'findInFolders' => 0,
		'loadFile1' => 0,
		'loadFile2' => 0,
	);

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
		require_once __DIR__ . '/AutoLoadFolders.php';
	}

	/**
	 * @return AutoLoad
	 */
	static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
			self::$instance->detectNadlibRoot();

			// should not be called before $this->useCookies is set
			//self::$instance->initFolders();
		}
		return self::$instance;
	}

	/**
	 * While loading Config, we need to make sure nadlib libraries can be loaded
	 */
	function postInit() {
		if (!$this->folders) {
			$this->folders = new AutoLoadFolders($this);
			$this->folders->loadConfig();
			if (class_exists('Config')) {
				self::$instance->config = Config::getInstance();
			}
			if (isset($_SESSION[__CLASS__])) {
				$this->classFileMap = isset($_SESSION[__CLASS__]['classFileMap'])
						? $_SESSION[__CLASS__]['classFileMap']
						: array();
			}
		}
	}

	function detectNadlibRoot() {
		$this->documentRoot = new Path($_SERVER['DOCUMENT_ROOT']);
		$this->documentRoot->resolveLink();

		$scriptWithPath = URL::getScriptWithPath();
		$relToNadlibCLI = URL::getRelativePath($scriptWithPath, dirname(__FILE__));
		$relToNadlibPU = URL::getRelativePath(getcwd(), dirname(__FILE__));
		if (class_exists('Config')) {
			//$config = Config::getInstance();	// can't do until autoload is registered
		}
		$this->nadlibRoot = dirname(__FILE__) . '/';
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

		$this->setComponentsPath();

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

	function setComponentsPath() {
		if (file_exists('composer.json')) {
			$json = json_decode(file_get_contents('composer.json'), 1);
			//debug($json['config']);
			if (isset($json['config'])
				&& isset($json['config']['component-dir'])) {
				$this->componentsPath = new Path($json['config']['component-dir']);
				$this->componentsPath->remove('public');
				$this->componentsPath = $this->componentsPath->relativeFromAppRoot();
			}
		}
		if (!$this->componentsPath) {
			$this->componentsPath = new Path($this->appRoot);
			$this->componentsPath->setAsDir();
			if (!$this->componentsPath->appendIfExists('components')) {
				$this->componentsPath->up();
				if (!$this->componentsPath->appendIfExists('components')) {
					$this->componentsPath->up();
					if (!$this->componentsPath->appendIfExists('components')) {
						$this->componentsPath = new Path($this->documentRoot);
						if ($this->componentsPath->appendIfExists('components')) {    // no !
							//$this->componentsPath = $this->componentsPath->relativeFromDocRoot();	// to check exists()
						}
					}
				}
			}
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
		while ($appRoot && ($appRoot != '/' && $appRoot != '\\')
			&& !($appRoot{1} == ':' && strlen($appRoot) == 3)	// u:\
		) {
			$exists = file_exists($appRoot.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class.Config.php');
			//debug($appRoot, strlen($appRoot), $exists);
			if ($exists) {
				break;
			}
			$exists = file_exists($appRoot.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'Config.php');
			//debug($appRoot, strlen($appRoot), $exists);
			if ($exists) {
				break;
			}
			$appRoot = dirname($appRoot);
		}

		if (!$appRoot || $appRoot == '/') {  // nothing is found by previous method
			$appRoot = new Path(realpath(dirname(URL::getScriptWithPath())));
			//debug($appRoot, URL::getScriptWithPath());
			$appRoot->upIf('nadlib');
			$appRoot->upIf('spidgorny');
			$appRoot->upIf('vendor');
		}

		// always add trailing slash!
	    $appRoot = cap($appRoot, '/');
		$appRoot = new Path($appRoot);
		return $appRoot;
	}

	function __destruct() {
		if ($this->useCookies) {
			$_SESSION[__CLASS__]['classFileMap'] = $this->classFileMap;
		}
		//debug($this->stat, $this->classFileMap, $this->folders);
	}

	/**
	 * Main __autoload() function
	 * @param $class
	 * @return bool
	 * @throws Exception
	 */
	function load($class) {
		/** @var TaylorProfiler $tp */
		//$tp = TaylorProfiler::getInstance();
		$tp = NULL;
		if ($tp) $tp->start(__METHOD__);
		$this->count++;

		$file = $this->loadFileForClass($class);

		if (!class_exists($class) && !interface_exists($class)) {
			if (isset($_SESSION)) {
				//debug('clear folder as '.$class.' is not found');
				//$this->folders = array();				// @see __destruct(), commented as it's too global
				$this->folders->clearCache();
				//debug($_SESSION[__CLASS__]['folders']);
				$this->useCookies = false;				// prevent __destruct saving data to the session
			}
			//debug($this->folders);
			if (false && class_exists('Config')) {
				$config = Config::getInstance();
				if ($config->config['autoload']['notFoundException']) {
					if ($tp) $tp->stop(__METHOD__);
					throw new Exception('Class '.$class.' ('.$file.') not found.'.BR);
				}
			} else {
				//debug_pre_print_backtrace();
				//pre_print_r($file, $this->folders->folders, $this->folders->collectDebug);
				$this->log(__METHOD__.': '.$class.' not found'.BR);
			}
			//echo '<font color="red">'.$classFile.'-'.$file.'</font> ';
			if ($tp) $tp->stop(__METHOD__);
			return false;
		} else {
			//echo $classFile.' ';
			if ($tp) $tp->stop(__METHOD__);
			return true;
		}
	}

	function loadFileForClass($class) {
		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);				// why?

		$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
		$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);	// Download

		$file = isset($this->classFileMap[$class]) ? $this->classFileMap[$class] : NULL;
		$file2 = str_replace('class.', '', $file);

		//echo $class.' ['.$file.'] '.(file_exists($file) ? "YES" : "NO").'<br />'."\n";

		//pre_print_r($class, $file, $file2);
		if ($file && file_exists($file)) {
			/** @noinspection PhpIncludeInspection */
			include_once $file;
			$this->stat['loadFile1']++;
		} elseif ($file2 && file_exists($file2)) {
			/** @noinspection PhpIncludeInspection */
			include_once $file2;
			$this->stat['loadFile2']++;
		} else {
			$ns = $subFolders ?:
					(sizeof($namespaces) > 1)
							? first($namespaces)
							: NULL;
			$this->folders->collectDebug = array();

			$file = $this->folders->findInFolders($classFile, $ns);
			$this->classFileMap[$class] = $file;
			if ($file) {
				if ($this->debug && class_exists('AppController', false)) {
					$subject = 'Class ['.$class.'] loaded from ['.$classFile.']';
					//$this->log($subject);
					$c = new AppController();
					echo $c->encloseInToggle(implode("\n", $this->folders->collectDebug), $subject);
				}

				/** @noinspection PhpIncludeInspection */
				include_once $file;
				$this->classFileMap[$class] = $file;
				$this->stat['findInFolders']++;
			} elseif ($this->debug) {
				//debug($this->stat['folders'], $this->stat['configPath']);
				//debug($this->folders);
			}
			$this->folders->collectDebug = null;
		}
		return $file;
	}

	function log($debugLine) {
		if ($this->debug && ifsetor($_COOKIE['debug'])) {
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

	function addFolder($path, $namespace = NULL) {
		$this->folders->addFolder($path, $namespace);
	}

}
