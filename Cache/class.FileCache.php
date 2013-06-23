<?php

class FileCache {
	protected $folder = 'cache/';
	protected $age = 86400; //60*60*24;

	function __construct($age = NULL) {
		if ($age) {
			$this->age = $age;
		}
		if (!is_writable($this->folder)) {
			throw new Exception('Folder '.$this->folder.' is not writable');
		}
	}

	function map($key) {
		return $this->folder . md5($key);
	}

	function hasKey($key) {
		$f = $this->map($key);
		$has = file_exists($f) && (time() - filemtime($f) < $this->age);
		if (!$has) {
			@unlink($f);
		}
		return $has;
	}

	function set($key, $val) {
		$con = Index::getInstance()->controller;
		$con->log('Writing cache to '.$this->map($key).', size: '.strlen($val), __CLASS__);
		file_put_contents($this->map($key), $val);
	}

	function get($key) {
		return file_get_contents($this->map($key));
	}

}
