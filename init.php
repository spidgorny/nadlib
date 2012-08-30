<?php

function __autoload($class) {
	if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
	require_once dirname(__FILE__).'/../nadlib/class.ConfigBase.php';
	require_once dirname(__FILE__).'/../class/class.Config.php';
	$folders = Config::$includeFolders
		? array_merge(ConfigBase::$includeFolders, Config::$includeFolders)
		: ConfigBase::$includeFolders;

	$classFile = end(explode('\\', $class));
	foreach ($folders as $path) {
		$file = dirname(__FILE__).DIRECTORY_SEPARATOR.$path.'/class.'.$classFile.'.php';
		//debug($file, file_exists($file));
		if (file_exists($file)) {
			include_once($file);
			break;
		}
	}
	if (!class_exists($class)) {
		debug($folders);
		throw new Exception('Class '.$class.' ('.$file.') not found.');
	}
	if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
}

define('DEVELOPMENT', isset($_COOKIE['debug']) ? $_COOKIE['debug'] : false);
if (DEVELOPMENT) {
	$GLOBALS['profiler'] = new TaylorProfiler(TRUE);
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', TRUE);
} else {
	error_reporting(0);
	ini_set('display_errors', FALSE);
	header('Cache-Control: max-age=0');
	header('Expires: Tue, 19 Oct 2010 13:24:46 GMT');
}
date_default_timezone_set('Europe/Berlin');

// remove cookies from $_REQUEST
//debug($_COOKIE);
foreach ($_COOKIE as $key => $_) {
	if ($_GET[$key]) {
		$_REQUEST[$key] = $_GET[$key];
	} else if ($_POST[$key]) {
		$_REQUEST[$key] = $_POST[$key];
	} else {
		unset($_REQUEST[$key]);
	}
}

function debug($a) {
	$params = func_get_args();
	call_user_func_array(array('Debug', 'debug_args'), $params);
}

function nodebug() {
}

function getDebug($a, $b = NULL, $c = '') {
	ob_start();
	debug($a);
	return ob_get_clean();
}

function startsWith($haystack, $needle) {
	return strpos($haystack, $needle) === 0;
}

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
