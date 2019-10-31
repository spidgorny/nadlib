<?php

if (!function_exists('str_startsWith')) {

	/**
	 * Whether string starts with some chars
	 * @param                 $haystack
	 * @param string|string[] $needle
	 * @return bool
	 */
	function str_startsWith($haystack, $needle)
	{
		if (!is_array($needle)) {
			$needle = [$needle];
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
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	function str_endsWith($haystack, $needle)
	{
		return strrpos($haystack, $needle) === (strlen($haystack) - strlen($needle));
	}

	if (!function_exists('str_contains')) {
		function str_contains($haystack, $needle)
		{
			if (is_array($haystack)) {
				debug_pre_print_backtrace();
			}
			return FALSE !== strpos($haystack, $needle);
		}
	}

	function str_icontains($haystack, $needle)
	{
		if (is_array($haystack)) {
			debug_pre_print_backtrace();
		}
		return FALSE !== stripos($haystack, $needle);
	}

	if (!function_exists('contains')) {
		function contains($haystack, $needle)
		{
			return str_contains($haystack, $needle);
		}
	}

	function containsAny($haystack, array $needle)
	{
		foreach ($needle as $n) {
			if (contains($haystack, $n)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Does string splitting with cleanup.
	 * Added array_pad() to prevent list() complaining about undefined index
	 * @param string $sep
	 * @param string $str
	 * @param int $max
	 * @return array
	 */
	function trimExplode($sep, $str, $max = null)
	{
		if (is_object($str)) {
			$is_string = method_exists($str, '__toString');
		} else {
			$is_string = is_string($str);
		}
		if (!$is_string) {
			debug('trimExplode', 'must be string', new htmlString(typ($str)));
//			debug_pre_print_backtrace();
		}
		if ($max) {
			$parts = explode($sep, $str, $max); // checked by isset so NULL makes it 0
		} else {
			$parts = explode($sep, $str);
		}
		$parts = array_map('trim', $parts);
		$parts = array_filter($parts);
		$parts = array_values($parts);
		$parts = array_pad($parts, $max, null);
		return $parts;
	}

	/**
	 * Replaces "\t" tabs in non breaking spaces so they can be displayed in html
	 *
	 * @param string $text
	 * @param int $tabDepth
	 * @return mixed
	 */
	function tab2nbsp($text, $tabDepth = 4)
	{
		$tabSpaces = str_repeat('&nbsp;', $tabDepth);
		return str_replace("\t", $tabSpaces, $text);
	}

	function tabify(array $fields)
	{
		static $lengths = [];
		foreach ($fields as $i => $f) {
			$len = mb_strlen($f);
			$lengths[$i] = max(ifsetor($lengths[$i]), $len);
		}
		foreach ($fields as $i => &$f) {
			$f = str_pad($f, $lengths[$i], ' ', STR_PAD_RIGHT);
		}
		$str = implode(TAB, $fields);
		return $str;
	}

	function cap($string, $with = '/')
	{
		$string .= '';
		if (!str_endsWith($string, $with)) {
			$string .= $with;
		}
		return $string;
	}

	function path_plus($path, $plus, $plus2 = null)
	{
		$freq = array_count_values(str_split($path));
		$separator = ifsetor($freq['/']) >= ifsetor($freq['\\']) ? '/' : '\\';

		$isAbs = isset($path[0]) &&
			($path[0] == '/' || $path[0] == '\\' || $path[1] == ':');

		$parts = trimExplode('/', $path.'');
		$parts = array_merge($parts, trimExplode('/', $plus));

		$root = '';
//		if (!Request::isWindows()) {
		if ($separator == '/') {	// not windows separator
			$root = ($isAbs ? $separator : '');
		}
		$string = $root . implode($separator, $parts);

		if ($plus2) {
			$string = path_plus($string, $plus2);
		}

		return $string;
	}

	function unquote($value, $start = ['\'', '"'], $end = ['\'', '"'])
	{
		if (is_string($start)) {
			$start = [$start];
		}
		if (is_string($end)) {
			$end = [$end];
		}
		if (!$value) {
			return $value;
		}
		if (!is_string($value)) {
			return $value;
		}
		foreach ($start as $s) {
			if ($value[0] == $s) {
				$value = trim($value, $s);
			}
		}
		foreach ($end as $e) {
			if ($value[strlen($value) - 1] == $e) {
				$value = trim($value, $e);
			}
		}
		return $value;
	}

	/**
	 * http://php.net/manual/en/function.str-replace.php#86177
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 * @return string
	 */
	function str_replace_once($search, $replace, $subject)
	{
		$firstChar = strpos($subject, $search);
		if ($firstChar !== false) {
			$beforeStr = substr($subject, 0, $firstChar);
			$afterStr = substr($subject, $firstChar + strlen($search));
			return $beforeStr . $replace . $afterStr;
		} else {
			return $subject;
		}
	}

	/**
	 * Convert string to in camel-case, useful for class name patterns.
	 *
	 * @param string $string
	 *   Target string.
	 *
	 * @return string
	 *   Camel-case string.
	 */
	function toCamelCase($string)
	{
		$string = str_replace('-', ' ', $string);
		$string = str_replace('_', ' ', $string);
		$string = ucwords(strtolower($string));
		$string = str_replace(' ', '', $string);
		return $string;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	function toDatabaseKey($string)
	{
		if (strtoupper($string) == $string) {
			return strtolower($string);
		}
		$out = '';
		$chars = preg_split('//u', $string, null, PREG_SPLIT_NO_EMPTY);
		foreach ($chars as $i => $ch) {
			if ($ch == ' ') {
				if ($out[-1] != '_') {
					$out .= '_';
				}
			} elseif (strtoupper($ch) == $ch) {
				if ($i) {
					if (strlen($out) && $out[strlen($out)-1] != '_') {
						$out .= '_';
					}
				}
				$out .= strtolower($ch);
			} else {
				$out .= $ch;
			}
		}
		return $out;
	}

}
