<?php

class MemcacheFile {
	protected $folder = 'cache/';

	function __construct() {
		$this->folder = Config::getInstance()->appRoot . DIRECTORY_SEPARATOR . $this->folder;
	}

	function map($key) {
		$key = str_replace('(', '-', $key);
		$key = str_replace(')', '-', $key);
		$key = str_replace('::', '-', $key);
		$file = $this->folder . $key . '.cache'; // str_replace('(', '-', str_replace(')', '-', $key))
		return $file;
	}

	function set($key, $val) {
		$file = $this->map($key);
		file_put_contents($file, serialize($val));
		@chmod($file, 0775);
	}

	function get($key, $expire = 0) {
		$file = $this->map($key);
		if ($expire && @filemtime($file) < time() - $expire) {

		} else {
			$val = @file_get_contents($file);
			if ($val) {
				$val = unserialize($val);
			}
		}
		return $val;
	}

	function clearCache($key) {
		$file = $this->map($key);
		if (file_exists($file)) {
			unlink($file);
		}
	}

}
