<?php

class MemcacheFile {

	/**
	 * @used in ClearCache
	 * @var string
	 */
	public $folder = 'cache/';

	public $key;

	public $expire = 0;

	/**
	 * If you define $key and $expire in the constructore
	 * you don't need to define it in each method below.
	 * Otherwise, please specify.
	 * @param null $key
	 * @param int $expire
	 */
	function __construct($key = NULL, $expire = 0) {
		if (MemcacheArray::$debug) {
			//echo __METHOD__.BR;
		}
		$sub = cap(AutoLoad::getInstance()->appRoot);

		if (!file_exists($sub.$this->folder)) {
			debug(__METHOD__, $sub, $this->folder);
			die();
		} else {
			$this->folder = $sub . $this->folder;
		}

		if ($key) {
			$this->key = $key;
		}
		if ($expire) {
			$this->expire = $expire;
		}
	}

	function map($key) {
		$key = str_replace('(', '-', $key);
		$key = str_replace(')', '-', $key);
		$key = str_replace('::', '-', $key);
		$key = str_replace(',', '-', $key);
		if (strpos($key, ' ') !== false || strpos($key, '/') !== false) {
			$key = md5($key);
		}
		$file = $this->folder . $key . '.cache'; // str_replace('(', '-', str_replace(')', '-', $key))
		return $file;
	}

	function set($key, $val) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$file = $this->map($key);
		if (is_writable($this->folder)) {
			file_put_contents($file, serialize($val));
			@chmod($file, 0777);	// needed for cronjob accessing cache files
		} else {
			if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
			throw new Exception($file.' write access denied.');
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function isValid($key = NULL, $expire = 0) {
		$key = $key ?: $this->key;
		$expire = $expire ?: $this->expire;
		$file = $this->map($key);
		$mtime = @filemtime($file);
		$bigger = ($mtime > (time() - $expire));
		if ($this->key == 'OvertimeChart::getStatsCached') {
//			debug($this->key, $file, $mtime, $expire, $bigger);
		}
		return /*!$expire ||*/ $bigger;
	}

	function get($key = NULL, $expire = 0) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$key = $key ?: $this->key;
		$expire = $expire ?: $this->expire;
		$file = $this->map($key);
		if ($this->isValid($key, $expire)) {
			$val = @file_get_contents($file);
			if ($val) {
				$val = unserialize($val);
			}
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $val;
	}

	function clearCache($key) {
		$file = $this->map($key);
		if (file_exists($file)) {
			//debug('<font color="green">Deleting '.$file.'</font>');
			unlink($file);
		}
	}

	/**
	 * @param $key
	 * @return Duration
	 */
	function getAge($key) {
		$file = $this->map($key);
		return new Duration(time() - @filemtime($file));
	}

/**
 * unfinished
 * static function getInstance($file, $expire) {
		$mf = new self();
		$get = $mf->get($file, $expire);
	}
 */
}
