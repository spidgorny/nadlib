<?php

/**
 * Initialized with a file parameter and represents a file which stores an array of values.
 * Each get/set will work with one value from this array
 * Class MemcacheArray
 */
class MemcacheArray implements ArrayAccess {
	public $file;
	protected $expire;
	/**
	 * Enter description here...
	 *
	 * @var MemcacheFile
	 */
	public $fc;
	public $data;

	protected $state;

	public static $instances = array();

	public $onDestruct;			// callback
	public $hit = 0;
	public $miss = 0;

	/**
	 *
	 *
	 * @param unknown_type $file - filename inside /cache/ folder
	 * @param unknown_type $expire - seconds to keep the cache active
	 */
	function __construct($file, $expire = 0) {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__.' ('.$file.')');
		$this->file = $file;
		$this->expire = $expire instanceof Duration ? $expire->getTimestamp() : $expire;
		$this->fc = new MemcacheFile();
		$this->data = $this->fc->get($this->file, $this->expire);
		$this->state = serialize($this->data);
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__.' ('.$file.')');
	}

	/**
	 * Saving always means that the expiry date is renewed upon each read
	 * Modified to save only on changed data
	 */
	function __destruct() {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		if ($this->onDestruct) {
			call_user_func($this->onDestruct, $this);
		}
		if ($this->fc && strcmp($this->state, serialize($this->data))) {
			$this->fc->set($this->file, $this->data);
		}
		//debug(sizeof($this->data));
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	function clearCache() {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		if ($this->fc) {
			$this->fc->clearCache($this->file);
		}
		unset(self::$instances[$this->file]);
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
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
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    static function getInstance($file, $expire = 0) {
    	return self::$instances[$file]
    		? self::$instances[$file]
    		: (self::$instances[$file] = new self($file, $expire));
    }

	static function unsetInstance($file) {
		if (self::$instances[$file]) {
			self::$instances[$file]->__destruct();
		}
		unset(self::$instances[$file]);
	}

}
