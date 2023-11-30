<?php

if (!function_exists('first')) {

	/**
	 * Complements the built-in end() function
	 * @param array $list
	 * @return array|mixed
	 */
	function first(array $list)
	{
		reset($list);
		return current($list);
	}
}

if (!function_exists('last')) {
	/**
	 * Complements the built-in end() function
	 * @param array $list
	 * @return array|mixed
	 */
	function last(array $list)
	{
		return end($list);
	}
}

if (!function_exists('eachv')) {
	/**
	 * This is equal to return next(each($list))
	 *
	 * @param array $list
	 * @return mixed
	 */
	function eachv(array &$list)
	{
		$current = current($list);
		next($list);
		return $current;
	}
}

if (!function_exists('array_combine_stringkey')) {
	/**
	 * Array_combine only works when both arrays are indexed by numbers
	 * @used FullGrid
	 * @param array $a
	 * @param array $b
	 * @return array
	 */
	function array_combine_stringkey(array $a, array $b)
	{
		$ret = [];
		reset($b);
		foreach ($a as $key) {
			$ret[$key] = current($b);
			next($b);
		}
		return $ret;
	}
}

if (!function_exists('is_assoc')) {
	/**
	 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
	 * @param $arr
	 * @return bool
	 */
	function is_assoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}

if (!function_exists('unique_multidim_array')) {
	/**
	 * Makes it unique on a first level only
	 * http://php.net/manual/en/function.array-unique.php#116302
	 * @param array $matriz
	 * @return array
	 */
	function unique_multidim_array(array $matriz)
	{
		$aux_ini = [];
		foreach ($matriz as $n => $source) {
			$aux_ini[$n] = serialize($source);
		}

		$mat = array_unique($aux_ini);

		$entrega = [];
		foreach ($mat as $n => $serial) {
			$entrega[$n] = unserialize($serial);

		}
		return $entrega;
	}

	function unique_multidim_array_thru(array $matriz)
	{
		foreach ($matriz as $n => &$source) {
			if (is_array($source)) {
				$source = unique_multidim_array_thru($source);
			}
		}

		return unique_multidim_array($matriz);
	}
}

if (!function_exists('array_to_string')) {
	/**
	 * Will call __toString() on every object in array
	 * @param array $a
	 * @return array
	 */
	function array_to_string(array $a)
	{
		foreach ($a as &$val) {
			if (is_array($val)) {
				$val = array_to_string($val);
			} else {
				$val = $val . '';
			}
		}
		return $a;
	}
}

if (!function_exists('without')) {
	function without(array $source, $remove)
	{
		if (phpversion() > 5.6) {
			return array_filter($source, function ($el, $key) use ($remove) {
				if (is_array($remove)) {
					return !in_array($key, $remove);
				} else {
					return $key != $remove;
				}
			}, ARRAY_FILTER_USE_BOTH);
		} else {
			return array_diff_key($source,
				array_flip((array)$remove));
		}
	}

}
