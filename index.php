<?php

if (!function_exists('__')) {
	function __($a, $sub1 = NULL, $sub2 = NULL, $sub3 = NULL)
	{
		$a = str_replace('%1', $sub1, $a);
		$a = str_replace('%2', $sub2, $a);
		$a = str_replace('%3', $sub3, $a);
		return $a;
	}
}

/** Should not be called $i because Index::getInstance() will return $GLOBALS['i'] */
$i2 = new NadlibIndex();
echo $i2->render();
