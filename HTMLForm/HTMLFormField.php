<?php

class HTMLFormField implements ArrayAccess {

	var $data = array();

	function __construct(array $desc) {
		$this->data = $desc;
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function &offsetGet($offset) {
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function getArray() {
		return $this->data;
	}

	public function getTypeString() {
		$type = ifsetor($this->data['type']);
		return is_string($type) ? $type : get_class($type);
	}

	public function isObligatory() {
		$type = $this->getTypeString();
		return !ifsetor($this->data['optional']) &&
		!in_array($type, array('check', 'checkbox', 'submit'));
	}

	public function isOptional() {
		return !$this->isObligatory();
	}

}
