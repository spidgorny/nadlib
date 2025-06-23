<?php

if (!function_exists('__')) {    // conflict with cakePHP

	/**
	 * @param string $code
	 * @param Array<string|int|float> ...$sub
	 * @return string
	 */
	function __($code, ...$sub)
	{
		$config = class_exists('Config') ? Config::getInstance() : null;

//		nodebug($code, (bool)$config,
//			is_object($config) ? get_class($config) : gettype($config),
//			(bool)$config->getLL());
		if (!empty($config) && $config->getLL()) {
			//echo '<pre>', get_class($index->ll), "\t", $code, "\t", $text, '</pre><br />', "\n";
			return $config->getLL()->T($code, ...$sub);
		}

		return LocalLang::Tp($code, ...$sub);
	}

	/**
	 * Same as __(), but calls only str_replace() without translating
	 * @param $code
	 * @param mixed $r1
	 * @param mixed $r2
	 * @param mixed $r3
	 * @return string
	 */
	function __p($code, $r1 = null, $r2 = null, $r3 = null)
	{
		$index = null;
		if (class_exists('Config')) {
			$index = Config::getInstance();
		}

		//debug(!!$index, get_class($index), !!$index->ll, get_class($index->ll));
		if ($index && $index->getLL()) {
			//echo '<pre>', get_class($index->ll), "\t", $code, "\t", $text, '</pre><br />', "\n";
			return $index->getLL()->Tp($code, $r1, $r2, $r3);
		}

		return $code;
	}

}
