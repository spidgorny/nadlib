<?php

require_once __DIR__.'/InitNADLIB.php';
require_once __DIR__.'/debugFunctions.php';
require_once __DIR__.'/stringFunctions.php';
require_once __DIR__.'/arrayFunctions.php';
require_once __DIR__.'/classFunctions.php';

if (!function_exists('parseFloat')) {

	function parseFloat($str) {
		preg_match_all('!\d+(?:\.\d+)?!', $str, $matches);
		$floats = array_map('floatval', $matches[0]);
		return ifsetor($floats[0]);
	}

	function parseFloat2($str) {
		return (float) filter_var( $str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
	}

	/**
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

/**
     * Shortcut for
     * isset($variable) ? $variable : $default
	 * BUT, it creates a NULL elements with the multidimensional arrays!!!
	 * @see http://nikic.github.io/2014/01/10/The-case-against-the-ifsetor-function.html
	 * @param $variable
	 * @param null $default
	 * @return null
	 * @see https://wiki.php.net/rfc/ifsetor
	 */
	function ifsetor(&$variable, $default = null) {
		if (isset($variable)) {
			$tmp = $variable;
		} else {
			$tmp = $default;
		}
		return $tmp;
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
	if (!function_exists('boolval')) {
		function boolval($val) {
			return (bool) $val;
		}
}
 * @param $something array
/**
 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
 * @param $arr
 * @return bool
 */
function is_assoc($arr) {
	return array_keys($arr) !== range(0, count($arr) - 1);
		}
		return $types;
	} else {
		return gettype2($something);
	}
	//return json_encode($types, JSON_PRETTY_PRINT);
}

function cap($string, $with = '/') {
	$string .= '';
	if (!str_endsWith($string, $with)) {
		$string .= $with;
	}
	return $string;
}

/**
 * Whether string starts with some chars
 * @param                 $haystack
 * @param string|string[] $needle
 * @return bool
 */
function str_startsWith($haystack, $needle) {
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
function str_endsWith($haystack, $needle) {
	return strrpos($haystack, $needle) === (strlen($haystack) - strlen($needle));
}

function str_contains($haystack, $needle) {
	if (is_array($haystack)) {
		debug_pre_print_backtrace();
	}
	return FALSE !== strpos($haystack, $needle);
}

