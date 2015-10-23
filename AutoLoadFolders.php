<?php

class AutoLoadFolders {

	/**
	 * @var array
	 */
	var $folders = array();

	/**
	 * If set $this->log will collect output here
	 * @var null|array
	 */
	public $collectDebug = null;

	public $debug = false;

	public $al;

	/**
	 * @var Debug
	 */
	protected $debugger;

	function __construct(AutoLoad $al) {
		$this->al = $al;
		require_once __DIR__.'/Debug/class.Debug.php';
		$this->debugger = Debug::getInstance();
		//if (isset($_SESSION[__CLASS__])) unset($_SESSION[__CLASS__]);
		$this->folders = $this->getFoldersFromSession();
		if ($this->folders) {
			$this->al->stat['folders'] = 'fromSession';
		} else {
			$this->al->stat['folders'] = 'fromConfig';
			$this->folders[NULL] = $this->getFolders();
		}
		$this->folders = unique_multidim_array($this->folders);
		if (0) {
			print '<pre>';
			//print_r($_SESSION[__CLASS__]);
			print_r($this->folders);
			print '</pre>';
		}
	}

	function getFoldersFromSession() {
		$folders = array();
		if (!Request::isCLI()) {
			if ($this->al->useCookies) {
				//debug('session_start', $this->nadlibFromDocRoot);
				//session_set_cookie_params(0, '');	// current folder
				if ((phpversion() < 5.4 || (
										phpversion() >= 5.4
										&& session_status() != PHP_SESSION_ACTIVE
								)
						) && !headers_sent() && $this->al->useCookies) {
					//echo '$this->useCookies', $this->useCookies, BR;
					//echo 'session_start ', __METHOD__, BR;
					//debug_pre_print_backtrace();
					session_start();
				}

				if (isset($_SESSION[__CLASS__])) {
					$folders = isset($_SESSION[__CLASS__]['folders'])
							? $_SESSION[__CLASS__]['folders']
							: array();
				}
			}
		}
		return $folders;
	}

	function getFolders() {
		require_once __DIR__ . '/HTTP/class.Request.php';
		$folders = array();

		$plus = $this->getFoldersFromConfig();
		$this->al->stat['folders'] .= ', '.sizeof($plus);
		$folders = array_merge($folders, $plus);		// should come first to override /be/

		$plus = $this->getFoldersFromConfigBase();
		$this->al->stat['folders'] .= ', '.sizeof($plus);
		$folders = array_merge($folders, $plus);
		//debug($folders);
		//debug($this->classFileMap, $_SESSION[__CLASS__]);

		return $folders;
	}

	function getFoldersFromConfig() {
		$this->loadConfig();    // make sure (again)
		$folders = array();
		if (class_exists('Config') && Config::$includeFolders) {
			$folders = Config::$includeFolders;
			// append $this->appRoot before each
			foreach ($folders as &$el) {
				$el = $this->al->appRoot . $el;
			}
		} else {
			// that's ok. relax. be quiet.
			//echo 'Config not found'.BR;
		}
		return $folders;
	}

	function loadConfig() {
		nodebug(array(
			dirname($_SERVER['SCRIPT_FILENAME']),
			getcwd(),
		));
		if (!class_exists('ConfigBase')) {
			require_once 'class.ConfigBase.php';
		}
		if (!class_exists('Config', false)) {
			//$configPath = dirname(URL::getScriptWithPath()).'/class/class.Config.php';
			$configPath1 = $this->al->appRoot.'class'.DIRECTORY_SEPARATOR.'class.Config.php';
			$configPath2 = $this->al->appRoot.'class'.DIRECTORY_SEPARATOR.      'Config.php';
			$this->al->stat['configPath'] = $configPath1;
			//debug($configPath, file_exists($configPath)); exit();
			if (file_exists($configPath1)) {
				/** @noinspection PhpIncludeInspection */
				include_once $configPath1;
			} elseif (file_exists($configPath2)) {
				/** @noinspection PhpIncludeInspection */
				include_once $configPath2;
				//print('<div class="message">'.$configPath.' FOUND.</div>'.BR);
			} else {
				// some projects don't need Config
				//print('<div class="error">'.$configPath.' not found.</div>'.BR);
			}
		}
	}

	function getFoldersFromConfigBase() {
		$folders = ConfigBase::$includeFolders;	// only ConfigBase here
		// append $this->nadlibRoot before each
		//if (basename(getcwd()) != 'be') {
		foreach ($folders as &$el) {
			$el = $this->al->nadlibRoot . $el;
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
		$_SESSION[__CLASS__]['folders'] = $this->folders;
	}

	function addFolder($path, $namespace = NULL) {
		if ($path[0] != '/') {
			$path = getcwd().'/'.$path;
		}
		$this->folders[$namespace][] = realpath($path);
		$sub = glob($path.'/*', GLOB_ONLYDIR);
		//debug($path, $sub);
		foreach ($sub as $s) {
			$this->addFolder($s, $namespace);
		}
		$this->folders = unique_multidim_array($this->folders);
	}

	/**
	 * @param $classFile
	 * @param $namespace
	 * @return string
	 */
	function findInFolders($classFile, $namespace) {
		//pre_var_dump($classFile, $namespace);
		//$appRoot = class_exists('Config') ? $this->config->appRoot : '';
		//foreach ($this->folders as $namespace => $map) {
		$map = ifsetor(
				$this->folders[$namespace],
				$this->folders[NULL]
		);
		assert(sizeof($map));
		//pre_print_r(array_keys($this->folders), array_keys($map), sizeof($map));
		foreach ($map as $path) {
			$file =
				//dirname(__FILE__).DIRECTORY_SEPARATOR.
				//dirname($_SERVER['SCRIPT_FILENAME']).DIRECTORY_SEPARATOR.
				//$this->nadlibRoot.
				$path.DIRECTORY_SEPARATOR.
				//cap($namespace).//DIRECTORY_SEPARATOR.
				'class.'.$classFile.'.php';
			$file2 = str_replace(DIRECTORY_SEPARATOR.'class.', DIRECTORY_SEPARATOR, $file);
			if ($namespace) {
				//pre_print_r($file, $file2);
			}
			// pre-check for file without "class." prefix
			if (!file_exists($file)) {
				if (file_exists($file2)
					&& !(
						basename($file2) == 'Index.php'
						&& basename(dirname(realpath($file2))) == 'nadlib'
					)
				) {	// on windows exclude index.php
					$file = $file2;
				}
			} else {
				$file2 = NULL;
			}

			if (file_exists($file)) {
				$this->log($classFile.' <span style="color: green;">'.$file.'</span>: YES<br />'."\n");
				$this->log($classFile.' <span style="color: green;">'.$file2.'</span>: YES<br />'."\n");
				//pre_var_dump('Found', $file);
				return $file;
			} else {
				$this->log($classFile.' <span style="color: red;">'.$file.'</span>: no<br />'."\n");
				$this->log($classFile.' <span style="color: red;">'.$file2.'</span>: no<br />'."\n");
			}
		}
		return NULL;
	}

	function log($debugLine) {
		if ($this->collectDebug !== null) {
			$this->collectDebug[] = $debugLine;
		} elseif ($this->debug && $_COOKIE['debug']) {
			if (Request::isCLI()) {
				echo strip_tags($debugLine);
			} else {
				echo $debugLine;
			}
		}
	}

}