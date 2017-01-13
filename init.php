<?php

require_once __DIR__ . '/Base/InitNADLIB.php';
require_once __DIR__ . '/functions/debugFunctions.php';
require_once __DIR__ . '/functions/stringFunctions.php';
require_once __DIR__ . '/functions/arrayFunctions.php';
require_once __DIR__ . '/functions/classFunctions.php';

if (!function_exists('parseFloat')) {

	function parseFloat($str) {
		preg_match_all('!\d+(?:\.\d+)?!', $str, $matches);
		$floats = array_map('floatval', $matches[0]);
		return ifsetor($floats[0]);
	}

	function parseFloat2($str) {
		return (float)filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}

	/**
	 * Shortcut for
	 * isset($variable) ? $variable : $default
	 * BUT, it creates a NULL elements with the multidimensional arrays!!!
	 * @see http://nikic.github.io/2014/01/10/The-case-against-the-ifsetor-function.html
	 * @param      $variable
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

	if (!function_exists('boolval')) {
		function boolval($val) {
			return (bool)$val;
		}
	}

}
