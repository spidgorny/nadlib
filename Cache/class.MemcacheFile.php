<?php

class MemcacheFile {
	protected $folder = 'cache/';

	function __construct() {
		$sub = Config::getInstance()->appRoot;

		if (!file_exists($sub.'/'.$this->folder)) {
			die(__METHOD__);
		} else {
			$this->folder = getcwd() . DIRECTORY_SEPARATOR . $this->folder;
			//debug($this->folder);
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
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$file = $this->map($key);
		if (is_writable($this->folder)) {
			file_put_contents($file, serialize($val));
			@chmod($file, 0777);	// needed for cronjob accessing cache files
		} else {
			throw new Exception($file.' write access denied.');
		}
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	function get($key, $expire = 0) {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$file = $this->map($key);
		if ($expire && @filemtime($file) < time() - $expire) {

		} else {
			$val = @file_get_contents($file);
			if ($val) {
				$val = unserialize($val);
			}
		}
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
		return $val;
	}

	function clearCache($key) {
		$file = $this->map($key);
		if (file_exists($file)) {
			debug('<font color="green">Deleting '.$file.'</font>');
			unlink($file);
		}
	}

/**
 * unfinished
 * static function getInstance($file, $expire) {
		$mf = new self();
		$get = $mf->get($file, $expire);
	}
	*/
}
