<?php

function __autoload($class) {
	if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
	//unset($_SESSION['autoloadCache']);
	$folders = $_SESSION['autoloadCache'];
	if (!$folders) {
		require_once('class.ConfigBase.php');
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

	$namespaces = explode('\\', $class);
	$classFile = end($namespaces);
	$subFolders = explode('/', $classFile);		// Download/GetAllRoutes
	$classFile = array_pop($subFolders);		// [Download, GetAllRoutes]
	$subFolders = implode('/', $subFolders);	// Download
	foreach ($folders as $path) {
		$file = dirname(__FILE__).DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$subFolders.'class.'.$classFile.'.php';
		if (file_exists($file)) {
			//echo $class.' <span style="color: green;">'.$file.'</span><br />';
			include_once($file);
			break;
		} else {
			//echo $class.' <span style="color: red;">'.$file.'</span>: '.file_exists($file).'<br />';
		}
	}
	if (!class_exists($class)) {
		//debug($folders);
		if (class_exists('Config')) {
			$config = Config::getInstance();
			if ($config->autoload['notFoundException']) {
				throw new Exception('Class '.$class.' ('.$file.') not found.');
			}
		}
	}
	if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
}

define('DEVELOPMENT', isset($_COOKIE['debug']) ? $_COOKIE['debug'] : false);
if (DEVELOPMENT) {
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', FALSE);
	//trigger_error(str_repeat('*', 20));	// log file separator
	ini_set('display_errors', TRUE);
	ini_set('html_error', TRUE);

	$profiler = new TaylorProfiler(TRUE);	// GLOBALS
	/* @var $profiler TaylorProfiler */
	if (class_exists('Config')) {
		//print_r(Config::getInstance()->config['Config']);
		set_time_limit(Config::getInstance()->config['Config']['timeLimit'] ? Config::getInstance()->config['Config']['timeLimit'] : 5);
	}
	$_REQUEST['d'] = isset($_REQUEST['d']) ? $_REQUEST['d'] : NULL;
} else {
	error_reporting(0);
	ini_set('display_errors', FALSE);
	header('Cache-Control: max-age=0');
	header('Expires: Tue, 19 Oct 2010 13:24:46 GMT');
}
date_default_timezone_set('Europe/Berlin');
Request::removeCookiesFromRequest();
chdir(dirname(dirname(__FILE__)));	// one level up

function debug($a) {
	$params = func_get_args();
	call_user_func_array(array('Debug', 'debug_args'), $params);
}

function nodebug() {
}

function getDebug() {
	ob_start();
	$params = func_get_args();
	call_user_func_array(array('Debug', 'debug_args'), $params);
	return ob_get_clean();
}

/**
 * Whether string starts with some chars
 * @param $haystack
 * @param $needle
 * @return bool
 */
function startsWith($haystack, $needle) {
	return strpos($haystack, $needle) === 0;
}

/**
 * Whether string ends with some chars
 * @param $haystack
 * @param $needle
 * @return bool
 */
function endsWith($haystack, $needle) {
	return strpos($haystack, $needle) === (strlen($haystack)-strlen($needle));
}

/**
 * Does string splitting with cleanup.
 * @param $sep
 * @param $str
 * @return array
 */
function trimExplode($sep, $str) {
	$parts = explode($sep, $str);
	$parts = array_map('trim', $parts);
	$parts = array_filter($parts);
	$parts = array_values($parts);
	return $parts;
}

function debug_pre_print_backtrace() {
	print '<pre>';
	if (phpversion() >= '5.3') {
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	} else {
		debug_print_backtrace();
	}
	print '</pre>';
}

/**
 * http://djomla.blog.com/2011/02/16/php-versions-5-2-and-5-3-get_called_class/
 */
if(!function_exists('get_called_class')) {
	function get_called_class($bt = false,$l = 1) {
		if (!$bt) $bt = debug_backtrace();
		if (!isset($bt[$l])) throw new Exception("Cannot find called class -> stack level too deep.");
		if (!isset($bt[$l]['type'])) {
			throw new Exception ('type not set');
		}
		else switch ($bt[$l]['type']) {
			case '::':
				$lines = file($bt[$l]['file']);
				$i = 0;
				$callerLine = '';
				do {
					$i++;
					$callerLine = $lines[$bt[$l]['line']-$i] . $callerLine;
				} while (stripos($callerLine,$bt[$l]['function']) === false);
				preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
					$callerLine,
					$matches);
				if (!isset($matches[1])) {
					// must be an edge case.
					throw new Exception ("Could not find caller class: originating method call is obscured.");
				}
				switch ($matches[1]) {
					case 'self':
					case 'parent':
						return get_called_class($bt,$l+1);
					default:
						return $matches[1];
				}
			// won't get here.
			case '->': switch ($bt[$l]['function']) {
				case '__get':
					// edge case -> get class of calling object
					if (!is_object($bt[$l]['object'])) throw new Exception ("Edge case fail. __get called on non object.");
					return get_class($bt[$l]['object']);
				default: return $bt[$l]['class'];
			}

			default: throw new Exception ("Unknown backtrace method type");
		}
	}
}

/**
 * Complements the built-in end() function
 * @param array $list
 * @return mixed
 */
function first(array $list) {
	reset($list);
	return current($list);
}

function array_combine_stringkey(array $a, array $b) {
	$ret = array();
	reset($b);
	foreach ($a as $key) {
		$ret[$key] = current($b);
		next($b);
	}
	return $ret;
}
