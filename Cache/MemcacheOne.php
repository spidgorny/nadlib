<?php

class MemcacheOne {

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var int
	 */
	protected $expires;

	/**
	 * @var MemcacheFile
	 */
	protected $mf;

	/**
	 * @var mixed
	 */
	protected $value;

	function __construct($key, $expires = 3600) {
		$this->key = $key;
		$this->expires = $expires;
		$this->mf = new MemcacheFile();
		$this->value = $this->mf->get($this->key, $this->expires);
	}

	function is_Set() {
		return !!$this->value;
	}

	function getValue() {
		return $this->value;
	}

	function set($newValue) {
		$this->mf->set($this->key, $newValue);
		$this->value = $newValue;
	}

	function getAge() {
		return $this->mf->getAge($this->key);
	}

	function map() {
		return $this->mf->map($this->key);
	}

}
