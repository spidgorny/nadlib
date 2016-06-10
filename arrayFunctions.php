<?php

if (!function_exists('first')) {

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
	 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
	 * @param $arr
	 * @return bool
	 */
	function is_assoc($arr)	{
		return array_keys($arr) !== range(0, count($arr) - 1);
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

	/**
	 * Will call __toString() on every object in array
	 * @param array $a
	 * @return array
	 */
	function array_to_string(array $a) {
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