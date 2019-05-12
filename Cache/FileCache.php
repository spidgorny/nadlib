<?php

/**
 * Stores a string and retrieves a string.
 * @see MemcacheFile if you need to store an array
 * Class FileCache
 */
class FileCache
{
	protected $folder = 'cache/';
	protected $age = 86400; //60*60*24;

	function __construct($age = NULL)
	{
		if ($age) {
			$this->age = $age;
		}
		if (!is_writable($this->folder)) {
			throw new Exception('Folder ' . $this->folder . ' is not writable');
		}
	}

	function map($key)
	{
		return $this->folder . md5($key) . '.cache';
	}

	function hasKey($key)
	{
		$f = $this->map($key);
		$has = file_exists($f) && (time() - filemtime($f) < $this->age);
		if (!$has) {
			@unlink($f);
		}
		return $has;
	}

	function set($key, $val)
	{
		if (is_array($val)) {
			//print_r($key);
			$val = serialize($val);
		}
		if (class_exists('Index')) {
			$con = Index::getInstance()->controller;
		}
		if (strlen($val)) {
			$con && $con->log(__METHOD__, 'Writing cache to <a href="' . $this->map($key) . '">' . $this->map($key) . ', size: ' . strlen($val));
			file_put_contents($this->map($key), $val);
		} else {
			$con && $con->log(__METHOD__, 'NOT writing cache because size: ' . strlen($val));
		}
	}

	function get($key, $default)
	{
		if ($this->hasKey($key)) {
			$string = file_get_contents($this->map($key));
			$try = @unserialize($string);
			if ($try !== false) {
				$string = $try;
			}
		} else {
			$string = is_callable($default) ? $default() : $default;
			$this->set($key, $string);
		}
		return $string;
	}

}
