<?php

class AutoLoad
{

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

	/**
	 * @var int
	 */
	public $count = 0;

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

	/**
	 * getFolders() is called from outside
	 * to be able to modify $useCookies
	 * #see register()
	 */
	protected function __construct()
	{
		require_once __DIR__ . '/HTTP/class.URL.php';
		require_once __DIR__ . '/HTTP/class.Request.php';

		$this->nadlibRoot = dirname(__FILE__) . '/';
		$this->appRoot = $this->detectAppRoot();
		$this->nadlibFromDocRoot = URL::getRelativePath($this->appRoot, realpath($this->nadlibRoot));
		$this->nadlibFromDocRoot = str_replace(dirname($_SERVER['SCRIPT_FILENAME']), '', $this->nadlibFromDocRoot) . '/';

		if (false) {
			echo '<pre>';
			print_r($this->debug());
			echo '</pre>';
		}

		$this->loadConfig();
	}

	function debug()
	{
		$scriptWithPath = URL::getScriptWithPath();
		$relToNadlibCLI = URL::getRelativePath($scriptWithPath, dirname(__FILE__));
		$relToNadlibPU = URL::getRelativePath(getcwd(), dirname(__FILE__));
		return array(
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
			'Config->documentRoot' => Config::getInstance()->documentRoot,
			'$this->appRoot' => $this->appRoot,
			'Config->appRoot' => Config::getInstance()->appRoot,
			'$this->nadlibFromDocRoot' => $this->nadlibFromDocRoot,
			'request->getDocumentRoot()' => Request::getInstance()->getDocumentRoot(),
			'request->getLocation()' => Request::getInstance()->getLocation(),
		);
	}

	/**
	 * Original idea was to remove vendor/s/nadlib/be from the CWD
	 * but since $this->nadlibRoot is relative "../" it's impossible.
	 * Now we go back one folder until we find "class/class.Config.php" which MUST exist
	 */
	function detectAppRoot()
	{
		$appRoot = dirname(URL::getScriptWithPath());
		$appRoot = realpath($appRoot);
		//debug('$this->appRoot', $this->appRoot, $this->nadlibRoot);
		//$this->appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $this->appRoot);
		while ($appRoot && $appRoot != '/'
			&& !($appRoot{1} == ':' && strlen($appRoot) == 3)    // u:\
		) {
			$exists = file_exists($appRoot . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class.Config.php');
			//debug($appRoot, strlen($appRoot), $exists);
			if ($exists) {
				break;
			}
			$appRoot = dirname($appRoot);
		}
		return $appRoot . DIRECTORY_SEPARATOR;
	}

	function loadConfig()
	{
		nodebug(array(
			dirname($_SERVER['SCRIPT_FILENAME']),
			getcwd(),
		));
		if (!class_exists('ConfigBase')) {
			require_once 'class.ConfigBase.php';
		}
		if (!class_exists('Config')) {
			//$configPath = dirname(URL::getScriptWithPath()).'/class/class.Config.php';
			$configPath = $this->appRoot . 'class' . DIRECTORY_SEPARATOR . 'class.Config.php';
			//var_dump($configPath, file_exists($configPath)); exit();
			if (file_exists($configPath)) {
				include_once $configPath;
				//print('<div class="message">'.$configPath.' FOUND.</div>'.BR);
			} else {
				// some projects don't need Config
				//print('<div class="error">'.$configPath.' not found.</div>'.BR);
			}
		}
	}

	function initFolders()
	{
		//if (isset($_SESSION[__CLASS__])) unset($_SESSION[__CLASS__]);
		$this->folders = $this->getFolders();
		if (false) {
			print '<pre>';
			print_r($_SESSION[__CLASS__]);
			print_r($this->folders);
			print '</pre>';
		}
	}

	function getFolders()
	{
		require_once __DIR__ . '/HTTP/class.Request.php';
		$folders = array();
		if (!Request::isCLI()) {
			if ($this->useCookies) {
				//debug('session_start', $this->nadlibFromDocRoot);
				session_set_cookie_params(0, '');    // current folder
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
			$folders = array();
			$folders = array_merge($folders, $this->getFoldersFromConfig());        // should come first to override /be/
			$folders = array_merge($folders, $this->getFoldersFromConfigBase());
		}
		//debug($folders);

		return $folders;
	}

	function getFoldersFromConfig()
	{
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

	function getFoldersFromConfigBase()
	{
		$folders = ConfigBase::$includeFolders;    // only ConfigBase here
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

	function __destruct()
	{
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
	function load($class)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->count++;

		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);                // why?

		$subFolders = explode('/', $classFile);        // Download/GetAllRoutes
		$classFile = array_pop($subFolders);        // [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);    // Download

		$file = isset($this->classFileMap[$class]) ? $this->classFileMap[$class] : NULL;
		$file2 = str_replace('class.', '', $file);

		//echo $class.' ['.$file.'] '.(file_exists($file) ? "YES" : "NO").'<br />'."\n";

		if ($file && file_exists($file)) {
			include_once $file;
		} elseif ($file2 && file_exists($file2)) {
			include_once $file2;
		} else {
			$file = $this->findInFolders($classFile, $subFolders);
			if ($file) {
				include_once $file;
				$this->classFileMap[$class] = $file;
			}
		}

		if (!class_exists($class) && !interface_exists($class)) {
			unset($_SESSION[__CLASS__]['folders']);    // just in case
			//debug($this->folders);
			if (false && class_exists('Config')) {
				$config = Config::getInstance();
				if ($config->config['autoload']['notFoundException']) {
					if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
					throw new Exception('Class ' . $class . ' (' . $file . ') not found.');
				}
			}
			//echo '<font color="red">'.$classFile.'-'.$file.'</font> ';
		} else {
			//echo $classFile.' ';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function findInFolders($classFile, $subFolders)
	{
		foreach ($this->folders as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				//$this->nadlibRoot.
				$path . DIRECTORY_SEPARATOR .
				$subFolders .//DIRECTORY_SEPARATOR.
				'class.' . $classFile . '.php';

			// pre-check for file without "class." prefix
			if (!file_exists($file)) {
				$file2 = str_replace(DIRECTORY_SEPARATOR . 'class.', DIRECTORY_SEPARATOR, $file);
				if (file_exists($file2)) {
					$file = $file2;
				}
			}

			if (file_exists($file)) {
				$this->log($classFile . ' <span style="color: green;">' . $file . '</span>: YES<br />' . "\n");
				$this->classFileMap[$classFile] = $file;
				return $file;
			} else {
				$this->log($classFile . ' <span style="color: red;">' . $file . '</span>: no<br />' . "\n");
			}
		}
	}

	function log($debugLine)
	{
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
	static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	static function register()
	{
		$instance = self::getInstance();
		$instance->initFolders();
		spl_autoload_register(array($instance, 'load'), true, true);    // before composer
	}

}
