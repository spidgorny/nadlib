<?php

class MemcacheFile {
	protected $folder = 'cache/';

	function __construct() {
		// fix for relative path on eval and buglog
		$pathprefix = dirname(__FILE__);
		$full = strlen($pathprefix);
		$neg = strlen('nadlib/Cache');
		$end = $full - $neg;
		$sub = substr($pathprefix, 0, $end);

		if (!file_exists($sub.'/'.$this->folder)) {
			debug(__METHOD__, $sub.'/'.$this->folder);
			die();
		} else {
			$this->folder = getcwd() . DIRECTORY_SEPARATOR . $this->folder;
		}
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
