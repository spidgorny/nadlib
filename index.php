<?php

if (!function_exists('__')) {

	/**
	 * @param string $a
	 * @param Array<string|int|float> ...$sub
	 * @return string
	 */
	function __($a, ...$sub)
	{
		foreach ($sub as $i => $subValue) {
			$a = str_replace("%$i", $subValue, $a);
		}
		return $a;
	}
}
