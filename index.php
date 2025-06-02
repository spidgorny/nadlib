<?php

if (!function_exists('__')) {
	function __($a, ...$sub)
	{
		foreach ($sub as $i => $subValue) {
			$a = str_replace("%$i", $subValue, $a);
		}
		return $a;
	}
}

/** Should not be called $i because Index::getInstance() will return $GLOBALS['i'] */
//require_once __DIR__ . '/Controller/NadlibIndex.php';
//$i2 = new NadlibIndex();
//echo $i2->render();
