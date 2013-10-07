<?php

/**
 * Initialized with a file parameter and represents a file which stores an array of values.
 * Each get/set will work with one value from this array
 * Class MemcacheArray
 */
class MemcacheArray {
	public $file;
	protected $expire;
	/**
	 * Enter description here...
	 *
	 * @var MemcacheFile
	 */
	public $fc;
	public $data;

	function __construct($file, $expire = 0) {
		//debug($file); exit;
		$this->file = $file;
		$this->expire = $expire;
		$this->fc = new MemcacheFile();
		$this->data = $this->fc->get($this->file, $this->expire);
		//debug($this->data); exit;
	}

	function __destruct() {
		if ($this->fc) {
			$this->fc->set($this->file, $this->data);
		}
	}

	function clearCache() {
		if ($this->fc) {
			$this->fc->clearCache($this->file);
		}
	}

	function exists($key) {
		return isset($this->data[$key]);
	}

	function get($key) {
		return $this->data[$key];
	}

	/**
	 * __destruct should save
	 * @param $key
	 * @param $value
	 */
	function set($key, $value) {
		$this->data[$key] = $value;
	}

}
