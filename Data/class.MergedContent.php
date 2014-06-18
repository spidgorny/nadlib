<?php

class MergedContent implements ArrayAccess {

	var $content = array();

	function __construct(array $parts = array()) {
		$this->content = $parts;
	}

	function __toString() {
		return implode("\n", $this->content);
	}

	public function offsetExists($offset) {
		return isset($this->content[$offset]);
	}

	public function offsetGet($offset) {
		return $this->content[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->content[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->content[$offset]);
	}
}
