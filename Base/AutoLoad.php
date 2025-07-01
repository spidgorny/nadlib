<?php

use spidgorny\nadlib\HTTP\URL;

class AutoLoad
{

	private static ?AutoLoad $instance = null;

	/**
	 * @var ?AutoLoadFolders
	 */
	public $folders;

	/**
	 * @var bool
	 */
	public $useCookies = true;

	/**
	 * @var bool
	 */
	public $debug = false;

	public $debugStartup = 0;

	/**
	 * Session stored map of each class to a file.
	 * This prevents searching for each file.
	 * @var array
	 */
	public $classFileMap = [];

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * @var int
	 */
	public $count = 0;

	/**
	 * @var Path from the root of the domain to the application root.
	 * Used as a prefix for JS/CSS files.
	 * http://localhost:8080/[have-you-been-here]/index.php
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

	public $stat = [
		'findInFolders' => 0,
		'loadFile1' => 0,
		'loadFile2' => 0,
	];

	/**
	 * @var ?Path from the root of the OS to the application root
	 * Z:/web/have-you-been-here/
	 */
	protected $appRoot;

	/**
	 * getFolders() is called from outside
	 * to be able to modify $useCookies
	 * #see register()
	 */
	protected function __construct()
	{
		if (phpversion() < 5.3 && !defined('__DIR__')) {
			define('__DIR__', dirname(__FILE__));
		}

		require_once __DIR__ . '/../HTTP/URL.php';
		require_once __DIR__ . '/../HTTP/Request.php';
		require_once __DIR__ . '/../HTTP/Path.php';
		require_once __DIR__ . '/AutoLoadFolders.php';
	}

	public static function register(): ?AutoLoad
	{
		$instance = self::getInstance();
		if (!$instance->folders) {
			$instance->postInit();
		}

		// before composer <- incorrect
		// composer autoload is much faster and should be first
		$result = spl_autoload_register($instance->load(...), true, false);
		if ($result) {
			//echo __METHOD__ . ' OK'.BR;
		} else {
			//debug(phpversion());
			//debug(error_get_last());
			//debug(is_callable(array($instance, 'load')));
//			function __autoload($class) {
//				$instance = AutoLoad::getInstance();
//				$instance->load($class);
//			}
			return $instance;
		}

		return null;
	}

	public static function getInstance(): AutoLoad
	{
		if (!self::$instance instanceof AutoLoad) {
			self::$instance = new self();
			self::$instance->detectNadlibRoot();

			// should not be called before $this->useCookies is set
			//self::$instance->initFolders();
		}

		return self::$instance;
	}

	public function detectNadlibRoot(): void
	{
		$this->documentRoot = new Path($_SERVER['DOCUMENT_ROOT']);
		$this->documentRoot->resolveLink();
		$this->documentRoot = new Path(
			str_replace('/kunden', '', $this->documentRoot)
		); // 1und1.de

		$scriptWithPath = URL::getScriptWithPath();
		if ($this->debugStartup) {
			echo 'scriptWithPath: ', $scriptWithPath, BR;
		}

//		$relToNadlibCLI = URL::getRelativePath($scriptWithPath, dirname(__FILE__));
		$this->nadlibRoot = dirname(__DIR__) . '/';
		$this->appRoot = $this->detectAppRoot();
		if ($this->debugStartup) {
			echo 'appRoot: ', $this->appRoot, BR;
		}

		if ((strlen($this->appRoot) > 1) && !$this->appRoot->isAbsolute()) { // '/', 'w:\\'
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
				$relToNadlibPU = URL::getRelativePath(getcwd(), dirname(__DIR__));
				$this->nadlibFromDocRoot = $relToNadlibPU;
				$appRootIsRoot = '$relToNadlibPU';
			}
		}

		$this->nadlibFromDocRoot = str_replace(dirname($_SERVER['SCRIPT_FILENAME']), '', $this->nadlibFromDocRoot);
		$this->nadlibFromDocRoot = cap($this->nadlibFromDocRoot, '/');
		if ($this->debugStartup) {
			echo 'documentRoot: ', $this->documentRoot, BR;
			echo 'nadlibFromDocRoot: ', $this->nadlibFromDocRoot, BR;
			echo 'appRootIsRoot: ', $appRootIsRoot, BR;
		}

//		$this->nadlibFromCWD = URL::getRelativePath(getcwd(), $this->nadlibRoot);
		if ($this->debugStartup) {
			echo 'nadlibFromCWD: ', $this->nadlibFromCWD, BR;
		}

		$this->nadlibRoot = cap($this->nadlibRoot);
		if ($this->debugStartup) {
			echo __METHOD__, ' ', $this->nadlibRoot, BR;
		}

		$this->setComponentsPath();

//		if (0 !== 0) {
//			echo '<pre>';
//			print_r([
//				'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
//				'rp(SCRIPT_FILENAME)' => realpath($_SERVER['SCRIPT_FILENAME']),
//				'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
//				'documentRoot' => $this->documentRoot . '',
//				'getcwd()' => getcwd(),
//				'__FILE__' => __FILE__,
//				'$scriptWithPath' => $scriptWithPath,
//				'dirname(__FILE__)' => dirname(__FILE__),
//				'baseHref' => Request::getLocation() . '',
////				'$relToNadlibCLI' => $relToNadlibCLI,
////				'$relToNadlibPU' => $relToNadlibPU,
//				'$this->nadlibRoot' => $this->nadlibRoot,
////				'Config->documentRoot' => isset($config) ? $config->documentRoot : NULL,
//				'$this->appRoot' => $this->appRoot . '',
//				'appRootIsRoot' => $appRootIsRoot,
////				'Config->appRoot' => isset($config) ? $config->appRoot : null,
//				'$this->nadlibFromDocRoot' => $this->nadlibFromDocRoot,
//				'$this->nadlibFromCWD' => $this->nadlibFromCWD,
//				'request->getDocumentRoot()' => Request::getInstance()->getDocumentRoot() . '',
//				'request->getLocation()' => Request::getInstance()->getLocation() . '',
//				'this->componentsPath' => $this->componentsPath . '',
//			]);
//			echo '</pre>';
//		}
	}

	public function detectAppRoot(): Path
	{
		require_once __DIR__ . '/AppRootDetector.php';
		$ard = new AppRootDetector();
		return $ard->get();
	}

	public function setComponentsPath(): void
	{
		if (file_exists('composer.json')) {
			$json = json_decode(file_get_contents('composer.json'), true, 512, JSON_THROW_ON_ERROR);
			//debug($json['config']);
			if (isset($json['config'])
				&& isset($json['config']['component-dir'])) {
				$this->componentsPath = new Path($json['config']['component-dir']);
				$this->componentsPath->remove('public');
				$this->componentsPath = $this->componentsPath->relativeFromAppRoot();
			}
		}

//		if (!$this->componentsPath) {
//			$this->componentsPath = new Path($this->appRoot);
//			$this->componentsPath->setAsDir();
//			if (!$this->componentsPath->appendIfExists('components')) {
//				$this->componentsPath->up();
//				if (!$this->componentsPath->appendIfExists('components')) {
//					$this->componentsPath->up();
//					if (!$this->componentsPath->appendIfExists('components')) {
//						$this->componentsPath = new Path($this->documentRoot);
//						if ($this->componentsPath->appendIfExists('components')) {    // no !
//							//$this->componentsPath = $this->componentsPath->relativeFromDocRoot();	// to check exists()
//						}
//					}
//				}
//			}
//		}
	}

	/**
	 * While loading Config, we need to make sure nadlib libraries can be loaded
	 */
	public function postInit(): void
	{
		if (!$this->folders) {
			$this->folders = new AutoLoadFolders($this);
			$this->folders->debug = $this->debug;
			//$this->folders->loadConfig();	// called already
			if (class_exists('Config')) {
				self::$instance->config = Config::getInstance();
			}

			if (isset($_SESSION[__CLASS__])) {
				$this->classFileMap = $_SESSION[__CLASS__]['classFileMap'] ?? [];
			}

			if (ifsetor($_SERVER['argc']) && in_array('-al', (array)ifsetor($_SERVER['argv']))) {
				echo 'AutoLoad, debug mode', BR;
				$this->debug = true;
				$this->folders->debug = true;
				//					$this->folders->collectDebug = array();
			}
		}
	}

	public function getAppRoot()
	{
		if (!$this->appRoot) {
			$this->appRoot = $this->detectAppRoot();
		}

		return $this->appRoot;
	}

	public function setAppRoot($path): void
	{
		$this->appRoot = $path;
	}

	public function __destruct()
	{
		if ($this->useCookies) {
			$_SESSION[__CLASS__] = ifsetor($_SESSION[__CLASS__], []);
			$_SESSION[__CLASS__]['classFileMap'] = $this->classFileMap;
		}

		//debug($this->stat, $this->classFileMap, $this->folders);
	}

	/**
	 * Main __autoload() function
	 * @throws Exception
	 */
	public function load(string $class): void
	{
		$this->count++;

		$file = $this->loadFileForClass($class);

		if (!class_exists($class) && !interface_exists($class)) {
			if (isset($_SESSION)) {
				//debug('clear folder as '.$class.' is not found');
				//$this->folders = array();				// @see __destruct(), commented as it's too global
				$this->folders->clearCache();
				//debug($_SESSION['AutoLoadFolders']['folders']);
				$this->useCookies = false;                // prevent __destruct saving data to the session
			}

			//debug($this->folders);
			if (class_exists('Config', false)) {
				$config = Config::getInstance();
				$notFoundException = ifsetor($config->config['autoload']['notFoundException']);
			} else {
				$notFoundException = false;
			}

			if ($notFoundException) {
				debug($this->folders->folders);
				throw new Exception('Class ' . $class . ' (' . $file . ') not found.' . BR);
			}

//debug_pre_print_backtrace();
			//pre_print_r($file, $this->folders->folders, $this->folders->collectDebug);
			$this->logError($class . ' not found by AutoLoad');
			//echo '<font color="red">'.$classFile.'-'.$file.'</font> ';
		}

//echo $classFile.' ';
		$this->logSuccess($class . ' OK');
	}

	public function loadFileForClass(string $class)
	{
		$namespaces = explode('\\', $class);
		$classFile = end($namespaces);                // why?

		$subFolders = explode('/', $classFile);        // Download/GetAllRoutes
		$classFile = array_pop($subFolders);        // [Download, GetAllRoutes]
		$subFolders = implode('/', $subFolders);    // Download

		$file = $this->getFileFromMap($class);
		if ($file) {
			/** @noinspection PhpIncludeInspection */
			include_once $file;
		} else {
			$ns = $subFolders ?:
				(count($namespaces) > 1
					? first($namespaces)
					: '');
//			$this->folders->collectDebug = array();

			$file = $this->folders->findInFolders($classFile, $ns);
//			echo __METHOD__, TAB, $class, TAB, $ns, TAB, $classFile, TAB, $file, BR;
			if ($file) {
				$this->classFileMap[$class] = $file;    // save
				$this->logSuccess($class . ' found in ' . $file);
//				if (false
//					&& $this->debug
//					&& class_exists('AppController', false)
//					&& !Request::isCLI()) {
//					$subject = 'Class [' . $class . '] loaded from [' . $classFile . ']';
//					//$this->log($subject);
//					$c = new AppController();
//					echo $c->encloseInToggle(
//						implode("\n", $this->folders->collectDebug), $subject);
//				}

				/** @noinspection PhpIncludeInspection */
				include_once $file;
				$this->classFileMap[$class] = $file;
				$this->stat['findInFolders']++;

				$this->logSuccess($class . ' exists: ' . class_exists($class));
			} elseif ($this->debug) {
				//debug($this->stat['folders'], $this->stat['configPath']);
				//debug($this->folders);
				$this->logError($class . ' not in folders [' . count($this->folders->folders['']) . ']');
				//pre_print_r($this->classFileMap);
			}

			//$this->folders->collectDebug = null;
		}

		return $file;
	}

	public function getFileFromMap(string $class)
	{
		$file = $this->classFileMap[$class] ?? null;

		//echo $class.' ['.$file.'] '.(file_exists($file) ? "YES" : "NO").'<br />'."\n";

		//pre_print_r($class, $file, $file2);
		if (!$file) {
			return null;
		}

		if (file_exists($file)) {
			$this->stat['loadFile1']++;
		} else {
			$file2 = str_replace('class.', '', $file);
			if ($file2 && file_exists($file2)) {
				$this->stat['loadFile2']++;
				$file = $file2;
			} else {
				$this->logError($class . ' not found in classFileMap[' . count($this->classFileMap) . ']');
				//pre_print_r($this->classFileMap);
				$file = null;
			}
		}

		return $file;
	}

	public function logError(string $debugLine): void
	{
		if ($this->debug) {
			$this->dumpCSS();
			echo '<span class="debug error">' . $debugLine . '</span>', BR;
		}
	}

	public function dumpCSS(): void
	{
		static $once = 0;
		if (Request::isCLI()) {
			return;
		}

		echo '<style>
			.debug.error {
				background: lightpink;
				color: red;
			}
			.debug.success {
				background: lightgreen;
				color: green;
			}
		</style>';
		$once = 1;
	}

	public function logSuccess(string $debugLine): void
	{
		if ($this->debug) {
			$this->dumpCSS();
			echo '<span class="debug success">' . $debugLine . '</span>', BR;
		}
	}

	public function log($debugLine): void
	{
		if ($this->debug) {
			if (Request::isCLI()) {
				//echo strip_tags($debugLine);
				$STDERR = fopen('php://stderr', 'w+');
				fwrite($STDERR, strip_tags($debugLine));
			} else {
				echo $debugLine, BR;
			}
		}
	}

	public function addFolder($path, $namespace = null): static
	{
		if (!$this->folders) {
			$this->postInit();
		}

		$this->folders->addFolder($path, $namespace);
		return $this;
	}

	public function setDebug(): void
	{
		$this->debug = true;
		$this->folders->debug = true;
	}

}
