<?php

class Filter extends ArrayObject {

	protected $_preferences = [];

	protected $_request = [];

	protected $_default = [];

	function __construct(array $input = array()) {
		parent::__construct($input, ArrayObject::ARRAY_AS_PROPS);
		$this->setRequest($input);
	}

	function setPreferences(array $_preferences) {
		$this->_preferences = $_preferences;
	}

	function setRequest(array $_request) {
		$this->_request = $_request;
	}

	function setDefault(array $_default) {
		$this->_default = $_default;
	}

	function offsetGet($index) {
		if (isset($this->_request[$index])) {
			return $this->_request[$index];
		} elseif (isset($this->_preferences[$index])) {
			return $this->_preferences[$index];
		} elseif (isset($this->_default[$index])) {
			return $this->_default[$index];
		}
		return NULL;
	}

	function getArrayCopy() {
		// first array has priority (only append new)
		return $this->_request + $this->_preferences + $this->_default;
	}

	function getIterator()
	{
		return new ArrayIterator($this->getArrayCopy());
	}

}
