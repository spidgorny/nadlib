<?php

require_once __DIR__.'/class.InitNADLIB.php';

/**
 * May already be defined in TYPO3
 */
if (!function_exists('debug')) {
	/**
	 * @param ...$a mixed
	 */
	function debug($a) {
	    $params = func_num_args() == 1 ? $a : func_get_args();
		if (class_exists('Debug')) {
			$debug = Debug::getInstance();
			$debug->debug($params);
		} else {
			ob_start();
			var_dump($params);
			$dump = ob_get_clean();
			$dump = str_replace("=>\n", ' =>', $dump);
			if (!function_exists('xdebug_break')) {
				$dump = htmlspecialchars($dump);
			}
			echo '<pre>'.$dump.'</pre>';
			debug_pre_print_backtrace();
		}
	}
}

if (!function_exists('nodebug')) {

	/**
	 * @param ...$a
	 */
	function nodebug($a) {
	}

	function getDebug()	{
		$params = func_get_args();
		$debug = Debug::getInstance();
		$content = $debug::printStyles();
		if (ifsetor($params[1]) == Debug::LEVELS) {
			$levels = ifsetor($params[2]);
			$params[1] = $levels;
		}
		$content .= call_user_func_array(array($debug, 'view_array'), $params);
		return $content;
	}

	/**
	 * @param ..$a
	 */
	function pre_print_r($a) {
		echo '<pre style="white-space: pre-wrap;">';
		print_r(func_num_args() == 1 ? $a : func_get_args());
		echo '</pre>';
	}

	function get_print_r($a) {
		return '<pre style="white-space: pre-wrap;">'.
		print_r($a, true).
		'</pre>';
	}

	function pre_var_dump($a) {
		echo '<pre style="white-space: pre-wrap; font-size: 8pt;">';
		var_dump(func_num_args() == 1 ? $a : func_get_args());
		echo '</pre>';
	}

	function debug_once() {
		static $used = NULL;
		if (is_null($used)) {
			$used = array();
		}
		$trace = debug_backtrace();
		array_shift($trace); // debug_once itself
		$first = array_shift($trace);
		$key = $first['file'] . '.' . $first['line'];
		if (!ifsetor($used[$key])) {
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

	if (!function_exists('str_startsWith')) {
		/**
		 * Whether string starts with some chars
		 * @param $haystack
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
	}

	if (!function_exists('endsWith')) {
		/**
		 * Whether string ends with some chars
		 * @param $haystack
		 * @param $needle
		 * @return bool
		 */
		function endsWith($haystack, $needle) {
			return strrpos($haystack, $needle) === (strlen($haystack) - strlen($needle));
		}
	}

	if (!function_exists('contains')) {
		function contains($haystack, $needle) {
			return FALSE !== strpos($haystack, $needle);
		}
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
			$parts = explode($sep, $str, $max); // checked by isset so NULL makes it 0
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
			if (!Request::isCLI()) {
				print '<pre style="
				white-space: pre-wrap;
				background: #eeeeee;
				border-radius: 5px;
				padding: 0.5em;
				">';
			}
			ob_start();
			if (phpversion() >= '5.3.6') {
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			} else {
				debug_print_backtrace();
			}
			$content = ob_get_clean();
			$content = str_replace(dirname(getcwd()), '', $content);
			$content = str_replace('C:\\Users\\'.getenv('USERNAME').'\\AppData\\Roaming\\Composer\\vendor\\phpunit\\phpunit\\src\\', '', $content);
			echo $content;
			if (!Request::isCLI()) {
				print '</pre>';
			}
		}
	}

	/**
	 * Replaces "\t" tabs in non breaking spaces so they can be displayed in html
	 *
	 * @param $text
	 * @param int $tabDepth
	 * @return mixed
	 */
	function tab2nbsp($text, $tabDepth = 4) {
		$tabSpaces = str_repeat('&nbsp;', $tabDepth);
		return str_replace("\t", $tabSpaces, $text);
	}

	/**
	 * http://djomla.blog.com/2011/02/16/php-versions-5-2-and-5-3-get_called_class/
	 */
	if (!function_exists('get_called_class')) {
		function get_called_class($bt = false, $l = 1) {
			if (!$bt) $bt = debug_backtrace();
			if (!isset($bt[$l])) throw new Exception("Cannot find called class -> stack level too deep.");
			if (!isset($bt[$l]['type'])) {
				throw new Exception ('type not set');
			} else switch ($bt[$l]['type']) {
				case '::':
					$lines = file($bt[$l]['file']);
					$i = 0;
					$callerLine = '';
					do {
						$i++;
						$callerLine = $lines[$bt[$l]['line'] - $i] . $callerLine;
						$findLine = stripos($callerLine, $bt[$l]['function']);
					} while ($callerLine && $findLine === false);
					$callerLine = $lines[$bt[$l]['line'] - $i] . $callerLine;
					preg_match('/([a-zA-Z0-9\_]+)::' . $bt[$l]['function'] . '/',
						$callerLine,
						$matches);
					if (!isset($matches[1])) {
						// must be an edge case.
						throw new Exception ("Could not find caller class: originating method call is obscured.");
					}
					switch ($matches[1]) {
						case 'self':
						case 'parent':
							return get_called_class($bt, $l + 1);
						default:
							return $matches[1];
					}
				// won't get here.
				case '->':
					switch ($bt[$l]['function']) {
						case '__get':
							// edge case -> get class of calling object
							if (!is_object($bt[$l]['object'])) throw new Exception ("Edge case fail. __get called on non object.");
							return get_class($bt[$l]['object']);
						default:
							return $bt[$l]['class'];
					}

				default:
					throw new Exception ("Unknown backtrace method type");
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
	 * Complements the built-in end() function
	 * @param array $list
	 * @return array|mixed
	 */
	function last(array $list) {
		return end($list);
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

		foreach ($rClass->getMethods() as $rMethod) {
			try {
				// attempt to find method in parent class
				new ReflectionMethod($rClass->getParentClass()->getName(),
					$rMethod->getName());
				// check whether method is explicitly defined in this class
				if ($rMethod->getDeclaringClass()->getName()
					== $rClass->getName()
				) {
					// if so, then it is overriden, so add to array
					$array[] .= $rMethod->getName();
				}
			} catch (exception $e) { /* was not in parent class! */
			}
		}

		return $array;
	}

	/**
	 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
	 * @param $arr
	 * @return bool
	 */
	function is_assoc($arr)	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	function cap($string, $with = '/') {
		$string .= '';
		if (!endsWith($string, $with)) {
			$string .= $with;
		}
		return $string;
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
	 * http://php.net/manual/en/function.error-reporting.php#65884
	 * @param $value
	 * @return string
	 */
	function error2string($value) {
		$level_names = array(
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE' );
		if (defined('E_STRICT')) {
			$level_names[E_STRICT] = 'E_STRICT';
		}
		$levels = array();
		if (($value & E_ALL) == E_ALL) {
			$levels[] = 'E_ALL';
			$value &= ~E_ALL;
		}
		foreach ($level_names as $level=>$name) {
			if (($value & $level) == $level) {
				$levels[] = $name;
			}
		}
		return implode(' | ',$levels);
	}

	/**
	 * http://php.net/manual/en/function.array-unique.php#116302
	 * @param array $matriz
	 * @return array
	 */
	function unique_multidim_array(array $matriz) {
		$aux_ini = array();
		foreach ($matriz as $n => $source)
		{
			$aux_ini[$n]=serialize($source);
		}

		$mat=array_unique($aux_ini);

		$entrega=array();
		foreach ($mat as $n => $serial)
		{
			$entrega[$n]=unserialize($serial);

		}
		return $entrega;
	}

	function gettype2($something) {
		$type = gettype($something);
		if ($type == 'object') {
			$hash = md5(spl_object_hash($something));
			$hash = substr($hash, 0, 6);
			$type .= '['.get_class($something).'#'.$hash.']';
		}
		if ($type == 'string') {
			$type .= '[' . strlen($something) . ']';
		}
		if ($type == 'array') {
			$type .= '[' . sizeof($something) . ']';
		}
		return $type;
	}

	if (!function_exists('boolval')) {
		function boolval($val) {
			return (bool) $val;
		}
	}

}
