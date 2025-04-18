<?php

require_once __DIR__ . '/../static.php';

/**
 * May already be defined in TYPO3
 */
if (!function_exists('debug')) {

	/**
	 * @param mixed,...$a
	 */
	function debug(...$a)
	{
		$params = func_num_args() == 1 ? $a : func_get_args();
		if (class_exists(Debug::class)) {
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

	function debugList(array $a, $name = null)
	{
		$debug = Debug::getInstance();
		$debug->name = $name;
		foreach ($a as &$b) {
			$b = $b . '';
		}
		debug($a);
	}

	function debugTable(array $a)
	{
		debug(new slTable($a));
	}

	function ddie()
	{
		debug(func_get_args());
		die(__FUNCTION__ . '#' . __LINE__);
	}
}

if (!function_exists('d')) {

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
	 */
	function nodebug(...$a)
	{
	}

	function getDebug(...$a)
	{
		$params = func_get_args();
		$debug = Debug::getInstance();
		$dh = new DebugHTML($debug);
		$content = $dh->printStyles();
		if (ifsetor($params[1]) === DebugHTML::LEVELS) {
			$levels = ifsetor($params[2]);
			$params[1] = $levels;
		}
		$content .= call_user_func_array([$dh, 'view_array'], $params);
		return $content;
	}

	/**
	 * @param ..$a
	 * @noinspection ForgottenDebugOutputInspection
	 */
	function pre_print_r(...$a)
	{
		if (PHP_SAPI !== 'cli') {
			echo '<pre class="pre_print_r" style="white-space: pre-wrap;">';
			print_r(func_num_args() === 1 ? $a[0] : func_get_args());
			echo '</pre>';
		} else {
			print_r(func_num_args() === 1 ? $a[0] : func_get_args());
			echo PHP_EOL;
		}
	}

	function get_print_r(...$a)
	{
		return '<pre class="pre_print_r" style="white-space: pre-wrap;">' .
			print_r($a, true) .
			'</pre>';
	}

	/** @noinspection ForgottenDebugOutputInspection */
	function pre_var_dump(...$a)
	{
		echo '<pre class="pre_var_dump" style="white-space: pre-wrap; font-size: 8pt;">';
		var_dump(func_num_args() === 1 ? $a : func_get_args());
		echo '</pre>';
	}

	function debug_once(...$a)
	{
		static $used = null;
		if (is_null($used)) {
			$used = [];
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

	function debug_size(...$a)
	{
		if (is_object($a)) {
			$vals = get_object_vars($a);
			$keys = array_keys($vals);
		} else {
			$vals = $a;
			$keys = array_keys($a);
		}
		$assoc = [];
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

	function debug_get_backtrace()
	{
		ob_start();
		if (phpversion() >= '5.3.6') {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		} else {
			debug_print_backtrace();
		}
		$content = ob_get_clean();
		$content = str_replace(dirname(getcwd()), '', $content);
		$content = str_replace('C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Roaming\\Composer\\vendor\\phpunit\\phpunit\\src\\', '', $content);
		return $content;
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
			echo debug_get_backtrace();
			if (!Request::isCLI()) {
				print '</pre>';
			}
		}
	}

	/**
	 * http://php.net/manual/en/function.error-reporting.php#65884
	 * @param int $value
	 * @return string
	 */
	function error2string($value)
	{
		$level_names = [
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
			E_USER_NOTICE => 'E_USER_NOTICE'];
		if (defined('E_STRICT')) {
			$level_names[E_STRICT] = 'E_STRICT';
		}
		$levels = [];
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
	 * @param mixed $something
	 * @param bool $withHash
	 * @param null $isCLI
	 * @return HTMLTag
	 */
	function typ($something, $withHash = true, $isCLI = null)
	{
		if ($isCLI === null) {
			$isCLI = Request::isCLI();
		}
		$type = gettype($something);
		if ($type === 'object') {
			if ($withHash) {
				$hash = md5(spl_object_hash($something));
				$hash = substr($hash, 0, 6);
				require_once __DIR__ . '/../HTTP/Request.php';
				if (!Request::isCLI()) {
					require_once __DIR__ . '/../Value/Color.php';
					$color = new Color('#' . $hash);
					$complement = $color->getComplement();
					if (!$isCLI) {
						$hash = new HTMLTag('span', [
							'class' => 'tag',
							'style' => 'background: ' . $color . '; color: ' . $complement,
						], $hash);
					}
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

		if ($type === 'string') {
			$typeName .= '[' . strlen($something) . ']';
		}
		if ($type === 'array') {
			$typeName .= '[' . sizeof($something) . ']';
		}

		if (!Request::isCLI()) {
			return new HTMLTag('span', ['class' => $class], $typeName, true);
		}
		return $typeName;
	}

	/**
	 * @param array|mixed $something
	 * @return array|HtmlString|HTMLTag|string
	 */
	function gettypes($something)
	{
		if (is_array($something)) {
			$types = [];
			foreach ($something as $key => $element) {
				$types[$key] = trim(strip_tags(typ($element)));
			}
			return $types;
		}

		return typ($something);
	}

}

function invariant($value, $message = null)
{
	if (!$value) {
		throw new Exception($message ?: 'Invariant failure in ' . Debug::getCaller());
	}
}

if (!defined('JSON_THROW_ON_ERROR')) {
	define('JSON_THROW_ON_ERROR', 4194304);
}

if (!function_exists('llog')) {
	/**
	 * @throws JsonException
	 */
	function llog(...$args)
	{
		$caller = Debug::getCaller();
		$jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

		if (defined('JSON_UNESCAPED_LINE_TERMINATORS')) {
			$jsonOptions |= JSON_UNESCAPED_LINE_TERMINATORS;
		}

		$vars = array_map(static function ($el) {
			if (is_object($el) && !($el instanceof stdClass)) {
				return trim(strip_tags(typ($el)));
			}
			if (is_resource($el)) {
				return 'Resource #' . get_resource_id($el) . ' of ' . get_resource_type($el);
			}
			return $el;
		}, $args);

		if (count($vars) === 1) {
			$output = json_encode([
				'type' => get_debug_type(first($args)),
				'value' => first($vars)
			], $jsonOptions);
		} else {
			$output = json_encode($vars, $jsonOptions);
			if (strlen($output) > 80) {
				$output = json_encode(count($vars) === 1
					? [
						'type' => get_debug_type(first($args)),
						'value' => first($vars)
					] : $vars, $jsonOptions | JSON_PRETTY_PRINT);
			}
		}

		/** @noinspection ForgottenDebugOutputInspection */
		error_log($caller . ' ' . $output);
	}
}

if (!function_exists('get_debug_type')) {
	function get_debug_type($value)
	{
		if (is_object($value)) {
			return get_class($value);
		}
		return gettype($value);
	}
}
