<?php

/**
 * May already be defined in TYPO3
 */
if (!function_exists('debug')) {

	/**
	 * @param $a,... mixed|string|int
	 */
	function debug($a)
	{
		$params = func_num_args() == 1 ? $a : func_get_args();
		if (class_exists('Debug')) {
			$debug = Debug::getInstance();
			$debug->debug($params);
		} elseif (DEVELOPMENT) {
			ob_start();
			var_dump($params);
			$dump = ob_get_clean();
			$dump = str_replace("=>\n", ' =>', $dump);
			if (!function_exists('xdebug_break')) {
				$dump = htmlspecialchars($dump);
			}
			echo '<pre>' . $dump . '</pre>';
			debug_pre_print_backtrace();
		}
	}
}

if (!function_exists('debugList')) {

	function debugList(array $a, $name = NULL)
	{
		$debug = Debug::getInstance();
		$debug->name = $name;
		foreach ($a as &$b) {
			$b = $b . '';
		}
		debug($a);
	}

	function ddie()
	{
		debug(func_get_args());
		die(__FUNCTION__ . '#' . __LINE__);
	}

	function d($a)
	{
		$params = func_num_args() == 1 ? $a : func_get_args();
		if (DEVELOPMENT) {
			ob_start();
			var_dump($params);
			$dump = ob_get_clean();
			$dump = str_replace("=>\n", ' =>', $dump);
			if (!function_exists('xdebug_break')) {
				$dump = htmlspecialchars($dump);
			}
			echo '<pre>' . $dump . '</pre>';
		}
	}

	/**
	 * @param ...$a
	 * @param null $b
	 * @param null $c
	 * @param null $d
	 * @param null $e
	 * @param null $f
	 */
	function nodebug($a, $b = null, $c = null, $d = null, $e = null, $f = null)
	{
	}

	function getDebug()
	{
		$params = func_get_args();
		$debug = Debug::getInstance();
		$dh = new DebugHTML($debug);
		$content = $dh->printStyles();
		if (ifsetor($params[1]) == DebugHTML::LEVELS) {
			$levels = ifsetor($params[2]);
			$params[1] = $levels;
		}
		$content .= call_user_func_array(array($dh, 'view_array'), $params);
		return $content;
	}

	/**
	 * @param ..$a
	 */
	function pre_print_r($a)
	{
		if (php_sapi_name() !== 'cli') {
			echo '<pre class="pre_print_r" style="white-space: pre-wrap;">';
			print_r(func_num_args() == 1 ? $a : func_get_args());
			echo '</pre>';
		} else {
			print_r(func_num_args() == 1 ? $a : func_get_args());
		}
	}

	function get_print_r($a)
	{
		return '<pre class="pre_print_r" style="white-space: pre-wrap;">' .
			print_r($a, true) .
			'</pre>';
	}

	function pre_var_dump($a)
	{
		echo '<pre class="pre_var_dump" style="white-space: pre-wrap; font-size: 8pt;">';
		var_dump(func_num_args() == 1 ? $a : func_get_args());
		echo '</pre>';
	}

	function debug_once()
	{
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

	function debug_size($a)
	{
		if (is_object($a)) {
			$vals = get_object_vars($a);
			$keys = array_keys($vals);
		} else {
			$vals = $a;
			$keys = array_keys($a);
		}
		$assoc = array();
		foreach ($keys as $key) {
			$sxe = $vals[$key];
			if ($sxe instanceof SimpleXMLElement) {
				$sxe = $sxe->asXML();
			}
			//$len = strlen(serialize($vals[$key]));
			$len = strlen(json_encode($sxe));
			//$len = gettype($vals[$key]) . ' '.get_class($vals[$key]);
			$assoc[$key] = $len;
		}
		debug($assoc);
	}

	function debug_pre_print_backtrace()
	{
		if (DEVELOPMENT) {
			require_once __DIR__ . '/../HTTP/Request.php';
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
			$content = str_replace('C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Roaming\\Composer\\vendor\\phpunit\\phpunit\\src\\', '', $content);
			echo $content;
			if (!Request::isCLI()) {
				print '</pre>';
			}
		}
	}

	/**
	 * http://php.net/manual/en/function.error-reporting.php#65884
	 * @param $value
	 * @return string
	 */
	function error2string($value)
	{
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
			E_USER_NOTICE => 'E_USER_NOTICE');
		if (defined('E_STRICT')) {
			$level_names[E_STRICT] = 'E_STRICT';
		}
		$levels = array();
		if (($value & E_ALL) == E_ALL) {
			$levels[] = 'E_ALL';
			$value &= ~E_ALL;
		}
		foreach ($level_names as $level => $name) {
			if (($value & $level) == $level) {
				$levels[] = $name;
			}
		}
		return implode(' | ', $levels);
	}

	/**
	 * similar to gettype() but return more information depending on data type in HTML
	 * @param $something
	 * @param bool $withHash
	 *
	 * @return htmlString
	 */
	function typ($something, $withHash = true)
	{
		$type = gettype($something);
		if ($type == 'object') {
			if ($withHash) {
				$hash = md5(spl_object_hash($something));
				$hash = substr($hash, 0, 6);
				require_once __DIR__ . '/../HTTP/Request.php';
				if (!Request::isCLI()) {
					require_once __DIR__ . '/../HTML/Color.php';
					$color = new Color('#' . $hash);
					$complement = $color->getComplement();
					$hash = new HTMLTag('span', array(
						'class' => 'tag',
						'style' => 'background: ' . $color . '; color: ' . $complement,
					), $hash);
				}
				$typeName = get_class($something) . '#' . $hash;
			} else {
				$typeName = get_class($something);
			}
		} else {
			$typeName = $type;
		}

		$bulma = [
			'string' => 'is-primary',
			'NULL' => 'is-danger',
			'object' => 'is-warning',
			'array' => 'is-link',
			'boolean' => 'is-info',
			'integer' => 'is-success',
			'resource' => '',
		];
		$class = ifsetor($bulma[$type]) . ' tag';

		if ($type == 'string') {
			$typeName .= '[' . strlen($something) . ']';
		}
		if ($type == 'array') {
			$typeName .= '[' . sizeof($something) . ']';
		}

		return new HTMLTag('span', ['class' => $class], $typeName, true);
	}

	/**
	 * @param $something array|mixed
	 * @return array|htmlString
	 */
	function gettypes($something)
	{
		if (is_array($something)) {
			$types = array();
			foreach ($something as $key => $element) {
				$types[$key] = strip_tags(typ($element));
			}
			return $types;
		} else {
			return typ($something);
		}
		//return json_encode($types, JSON_PRETTY_PRINT);
	}

}
