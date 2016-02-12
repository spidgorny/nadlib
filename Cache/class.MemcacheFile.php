<?php

class MemcacheFile implements MemcacheInterface {

	/**
	 * @used in ClearCache
	 * @var string
	 */
	public $folder = 'cache/';

	public $key;

	public $expire = 0;

	/**
	 * If you define $key and $expire in the constructor
	 * you don't need to define it in each method below.
	 * Otherwise, please specify.
	 * @param null $key
	 * @param int $expire
	 */
	function __construct($key = NULL, $expire = 0) {
		if (MemcacheArray::$debug) {
			echo __METHOD__.'('.$key.')'.BR;
		}
		$sub = cap(AutoLoad::getInstance()->appRoot);

		if (!file_exists($sub.$this->folder)) {
			debug(array(
				'unable to access cache folder',
				'method' => __METHOD__,
				'appRoot' => $sub,
				'folder' => $this->folder));
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

	/**
	 * @param $key	- can be provided in the constructor, but repeated here for BWC
	 * @param $val
	 * @throws Exception
	 */
	function set($key, $val) {
		TaylorProfiler::start(__METHOD__);
		$file = $this->map($key);
		if (is_writable($this->folder)) {
			file_put_contents($file, serialize($val));
			@chmod($file, 0777);	// needed for cronjob accessing cache files
		} else {
			TaylorProfiler::stop(__METHOD__);
			throw new Exception($file.' write access denied.');
		}
		TaylorProfiler::stop(__METHOD__);
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

	/**
	 * @param null $key	- can be NULL to be used from the constructor
	 * @param int  $expire
	 * @return mixed|null|string
	 */
	function get($key = NULL, $expire = 0) {
		TaylorProfiler::start(__METHOD__);
		$val = NULL;
		$key = $key ?: $this->key;
		$expire = $expire ?: $this->expire;
		$file = $this->map($key);
		if ($this->isValid($key, $expire)) {
			$val = @file_get_contents($file);
			if ($val) {
				$try = @unserialize($val);
				if ($try) {
					$val = $try;
				}
			}
		}
		TaylorProfiler::stop(__METHOD__);
		return $val;
	}

	function clearCache($key) {
		$file = $this->map($key);
		if (file_exists($file)) {
			//echo '<font color="green">Deleting '.$file.'</font>', BR;
			unlink($file);
		} else {
			//echo '<font color="orange">Cache file'.$file.' does not exist.</font>', BR;
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
