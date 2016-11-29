<?php

if (!function_exists('str_startsWith')) {

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

	function str_icontains($haystack, $needle) {
		if (is_array($haystack)) {
			debug_pre_print_backtrace();
		}
		return FALSE !== stripos($haystack, $needle);
	}

	if (!function_exists('contains')) {
		function contains($haystack, $needle) {
			return str_contains($haystack, $needle);
		}
	}

	function containsAny($haystack, array $needle) {
		foreach ($needle as $n) {
			if (contains($haystack, $n)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Does string splitting with cleanup.
	 * @param $sep string
	 * @param $str string
	 * @param null $max
	 * @return array
	 */
	function trimExplode($sep, $str, $max = NULL) {
		if (is_object($str)) {
			$is_string = method_exists($str, '__toString');
		} else {
			$is_string = is_string($str);
		}
		if (!$is_string) {
			debug_pre_print_backtrace();
		}
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

	function tabify(array $fields) {
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

	function cap($string, $with = '/') {
		$string .= '';
		if (!str_endsWith($string, $with)) {
			$string .= $with;
		}
		return $string;
	}

	function unquote ($value) {
		if (!$value) return $value;
		if (!is_string($value)) return $value;
		if ($value[0] == '\'') return trim($value, '\'');
		if ($value[0] == '"') return trim($value, '"');
		return $value;
	}

	/**
	 * http://php.net/manual/en/function.str-replace.php#86177
	 * @param $search
	 * @param $replace
	 * @param $subject
	 * @return string
	 */
	function str_replace_once($search, $replace, $subject) {
		$firstChar = strpos($subject, $search);
		if ($firstChar !== false) {
			$beforeStr = substr($subject,0,$firstChar);
			$afterStr = substr($subject, $firstChar + strlen($search));
			return $beforeStr.$replace.$afterStr;
		} else {
			return $subject;
		}
	}

}
