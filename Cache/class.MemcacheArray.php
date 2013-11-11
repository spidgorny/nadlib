<?php

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
	 * @param string $file - filename inside /cache/ folder
	 * @param int $expire - seconds to keep the cache active
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
		$this->save();
		//debug(sizeof($this->data));
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	function save() {
		if ($this->fc && strcmp($this->state, serialize($this->data))) {
			debug(__METHOD__, $this->fc->map($this->file), sizeof($this->data), array_keys($this->data));
			$this->fc->set($this->file, $this->data);
		}
	}

	function clearCache() {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$prev = sizeof(self::$instances);
		$prevKeys = array_keys(self::$instances);
		self::unsetInstance($this->file);
		$curr = sizeof(self::$instances);
		//debug(__METHOD__, $this->file, $prev, $curr, $prevKeys, array_keys(self::$instances));
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
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
		if (self::$instances[$file]->fc) {
			self::$instances[$file]->fc->clearCache(self::$instances[$file]->file);
		}
		unset(self::$instances[$file]);
	}

}
