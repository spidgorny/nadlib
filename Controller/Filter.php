<?php

class Filter extends ArrayObject {

	protected $_set = [];

	protected $_request = [];

	protected $_preferences = [];

	protected $_default = [];

	function __construct(array $input = array()) {
		parent::__construct($input, ArrayObject::ARRAY_AS_PROPS);
		$this->setRequest($input);
	}

	function setPreferences(array $_preferences = NULL) {
		if ($_preferences) {
			$this->_preferences = $_preferences;
		}
	}

	function setRequest(array $_request) {
		$this->_request = $_request;
	}

	function setDefault(array $_default) {
		$this->_default = $_default;
	}

	function get($index) {
		return $this->offsetGet($index);
	}

	function getArray($index) {
		$value = $this->offsetGet($index);
		$value = (array)$value;
		return $value;
	}

	function set($index, $newval) {
		$this->offsetSet($index, $newval);
	}

	public function offsetSet($index, $newval)
	{
//		debug(__METHOD__, $index, $newval);
		$this->_set[$index] = $newval;
	}

	function offsetGet($index) {
		if (isset($this->_set[$index])) {
			return $this->_set[$index];
		} elseif (isset($this->_request[$index])) {
			return $this->_request[$index];
		} elseif (isset($this->_preferences[$index])) {
			return $this->_preferences[$index];
		} elseif (isset($this->_default[$index])) {
			return $this->_default[$index];
		}
		return NULL;
	}

	function offsetExists($index) {
		return $this->offsetGet($index) != '';
	}

	function getArrayCopy() {
		// first array has priority (only append new)
		return $this->_set +
			$this->_request +
			$this->_preferences +
			$this->_default;
	}

	function getIterator()
	{
		return new ArrayIterator($this->getArrayCopy());
	}

	function clear() {
		$this->_set = [];
		$this->_request = [];
		$this->_preferences = [];
		$this->_default = [];	// maybe it should remain?
	}

	function getDebug() {
		return [
			'set' => $this->_set,
			'request' => $this->_request,
			'preferences' => $this->_preferences,
			'default' => $this->_default,
		];
	}

	function __debugInfo() {
		return $this->getDebug();
	}

	function ensure($field, array $allowedOptions, $default = NULL) {
		$value = $this[$field];
		if (!ifsetor($allowedOptions[$value])) {
			$default = $default ?: first(array_keys($allowedOptions));
			$this->set($field, $default);
		}
	}

}
