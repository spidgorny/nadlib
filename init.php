<?php

define('BR', "<br />\n");

class InitNADLIB {

	var $useCookies = true;

	/**
	 * @var AutoLoad
	 */
	var $al;

	function __construct() {
		require_once dirname(__FILE__) . '/class.AutoLoad.php';
		$this->al = AutoLoad::getInstance();
	}

	function init() {
		//print_r($_SERVER);

		define('DEVELOPMENT', Request::isCLI()
			? (Request::isWindows() || false) // at home
			: (isset($_COOKIE['debug']) ? $_COOKIE['debug'] : false)
		);

		date_default_timezone_set('Europe/Berlin');	// before using header()
		Mb_Internal_Encoding ( 'UTF-8' );
		setlocale(LC_ALL, 'UTF-8');

		if (DEVELOPMENT) {
			header('X-nadlib: DEVELOPMENT');
			error_reporting(E_ALL ^ E_NOTICE);
			//ini_set('display_errors', FALSE);
			//trigger_error(str_repeat('*', 20));	// log file separator

			ini_set('display_errors', TRUE);
			ini_set('html_error', TRUE);
		} else {
			header('X-nadlib: PRODUCTION');
			error_reporting(0);
			ini_set('display_errors', FALSE);
		}

		$this->al->useCookies = $this->useCookies;
		$this->al->register();
        Config::getInstance();

		Config::getInstance();

		if (DEVELOPMENT) {
			$GLOBALS['profiler'] = new TaylorProfiler(true);	// GLOBALS
			/* @var $profiler TaylorProfiler */
			if (class_exists('Config')) {
				//print_r(Config::getInstance()->config['Config']);
				set_time_limit(Config::getInstance()->timeLimit
					? Config::getInstance()->timeLimit
					: 5);	// small enough to notice if the site is having perf. problems
			}
			$_REQUEST['d'] = isset($_REQUEST['d']) ? $_REQUEST['d'] : NULL;
			if (!Request::isCLI()) {
				header('Cache-Control: no-cache, no-store, max-age=0');
				header('Expires: -1');
			}
		} else {
			if (!Request::isCLI()) {
				header('Cache-Control: no-cache, no-store, max-age=0');
				header('Expires: -1');
			}
		}
		//ini_set('short_open_tag', 1);	// not working
		Request::removeCookiesFromRequest();

        // in DCI for example, we don't use composer (yet!?)
        if (file_exists('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
        }
	}

	function initWhoops() {
		$run     = new Whoops\Run;
		$handler = new Whoops\Handler\PrettyPageHandler;
		$run->pushHandler($handler);
		$run->register();
	}

}

/**
 * May already be defined in TYPO3
 */
if (!function_exists('debug')) {
function debug($a) {
    $params = func_get_args();
    if (method_exists('Debug', 'debug_args')) {
	    if (class_exists('FirePHP') && !Request::isCLI() && !headers_sent()) {
		    $fp = FirePHP::getInstance(true);
			if ($fp->detectClientExtension()) {
				$fp->setOption('includeLineNumbers', true);
				$fp->setOption('maxArrayDepth', 10);
				$fp->setOption('maxDepth', 20);
				$trace = Debug::getSimpleTrace();
				array_shift($trace);
				if ($trace) {
					$fp->table(implode(' ', first($trace)), $trace);
				}
				$fp->log(1 == sizeof($params) ? $a : $params);
			} else {
				call_user_func_array(array('Debug', 'debug_args'), $params);
			}
	    } else {
		    call_user_func_array(array('Debug', 'debug_args'), $params);
	    }
	} else {
		ob_start();
		var_dump(
			func_num_args() == 1 ? $a : $params
		);
		$dump = ob_get_clean();
		$dump = str_replace("=>\n", ' =>', $dump);
		echo '<pre>'.htmlspecialchars($dump).'</pre>';
	}
}
}

function nodebug() {
}

function getDebug() {
	ob_start();
	$params = func_get_args();
	call_user_func_array(array('Debug', 'debug_args'), $params);
	return ob_get_clean();
}

function pre_print_r($a) {
	echo '<pre style="white-space: pre-wrap;">';
	print_r($a);
	echo '</pre>';
}

function debug_once() {
	static $used = array();
	$trace = debug_backtrace();
	array_shift($trace);	// debug_once itself
	$first = array_shift($trace);
	$key = $first['file'].'.'.$first['line'];
	if (!$used[$key]) {
		$v = func_get_args();
		//$v[] = $key;
		call_user_func_array('debug', $v);
		$used[$key] = true;
	}
}

function debug_size($a) {
	if (is_object($a)) {
		$vals = get_object_vars($a);
		$keys = array_keys($vals);
	} else {
		$vals = $a;
		$keys = array_keys($a);
	}
	$assoc = array();
	foreach ($keys as $key) {
		if ($vals[$key] instanceof SimpleXMLElement) {
			$vals[$key] = $vals[$key]->asXML();
		}
		//$len = strlen(serialize($vals[$key]));
		$len = strlen(json_encode($vals[$key]));
		//$len = gettype($vals[$key]) . ' '.get_class($vals[$key]);
		$assoc[$key] = $len;
	}
	debug($assoc);
}

/**
 * Whether string starts with some chars
 * @param $haystack
 * @param string|string[] $needle
 * @return bool
 */
function startsWith($haystack, $needle) {
	if (!is_array($needle)) {
		$needle = array($needle);
	}
	foreach ($needle as $need) {
		if (strpos($haystack, $need) === 0) {
			return true;
		}
	}
	return false;
}

/**
 * Whether string ends with some chars
 * @param $haystack
 * @param $needle
 * @return bool
 */
function endsWith($haystack, $needle) {
	return strrpos($haystack, $needle) === (strlen($haystack)-strlen($needle));
}

/**
 * Does string splitting with cleanup.
 * @param $sep
 * @param $str
 * @param null $max
 * @return array
 */
function trimExplode($sep, $str, $max = NULL) {
	if ($max) {
		$parts = explode($sep, $str, $max);		// checked by isset so NULL makes it 0
	} else {
		$parts = explode($sep, $str);
	}
	$parts = array_map('trim', $parts);
	$parts = array_filter($parts);
	$parts = array_values($parts);
	return $parts;
}

function debug_pre_print_backtrace() {
	if (DEVELOPMENT) {
		print '<pre>';
		if (phpversion() >= '5.3') {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		} else {
			debug_print_backtrace();
		}
		print '</pre>';
	}
}

/**
 * Replaces "\t" tabs in non breaking spaces so they can be displayed in html
 *
 * @param $text
 * @param int $tabDepth
 * @return mixed
 */
function tab2nbsp ($text, $tabDepth = 4) {
    $tabSpaces = str_repeat('&nbsp;', $tabDepth);
    return str_replace("\t", $tabSpaces, $text);
}

/**
 * http://djomla.blog.com/2011/02/16/php-versions-5-2-and-5-3-get_called_class/
 */
if(!function_exists('get_called_class')) {
	function get_called_class($bt = false, $l = 1) {
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
					$findLine = stripos($callerLine, $bt[$l]['function']);
				} while ($callerLine && $findLine === false);
				$callerLine = $lines[$bt[$l]['line']-$i] . $callerLine;
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
 * @return array|mixed
 */
function first(array $list) {
	reset($list);
	return current($list);
}

/**
 * This is equal to return next(each($list))
 *
 * @param array $list
 * @return mixed
 */
function eachv(array &$list) {
	$current = current($list);
	next($list);
	return $current;
}

/**
 * @used FullGrid
 * @param array $a
 * @param array $b
 * @return array
 */
function array_combine_stringkey(array $a, array $b) {
	$ret = array();
	reset($b);
	foreach ($a as $key) {
		$ret[$key] = current($b);
		next($b);
	}
	return $ret;
}

/**
 * http://www.php.net/manual/en/function.get-class-methods.php
 * @param $class
 * @return array|null
 */
function get_overriden_methods($class) {
	$rClass = new ReflectionClass($class);
	$array = NULL;

	foreach ($rClass->getMethods() as $rMethod)
	{
		try
		{
			// attempt to find method in parent class
			new ReflectionMethod($rClass->getParentClass()->getName(),
				$rMethod->getName());
			// check whether method is explicitly defined in this class
			if ($rMethod->getDeclaringClass()->getName()
				== $rClass->getName())
			{
				// if so, then it is overriden, so add to array
				$array[] .=  $rMethod->getName();
			}
		}
		catch (exception $e)
		{    /* was not in parent class! */    }
	}

	return $array;
}

/**
 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
 * @param $arr
 * @return bool
 */
function is_assoc($arr) {
	return array_keys($arr) !== range(0, count($arr) - 1);
}
