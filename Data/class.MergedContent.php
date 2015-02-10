<?php

/**
 * Class MergedContent
 * This behaves like a string.
 * But you can also access parts of the string separately.
 * Useful when some HTML content is generated in different parts.
 * Sometime you need to output the whole content,
 * sometimes you only need a part of it.
 */
class MergedContent implements ArrayAccess {

	var $content = array();

	function __construct(array $parts = array()) {
		$this->content = $parts;
	}

	function __toString() {
		return IndexBase::mergeStringArrayRecursive($this->content);
	}

	public function offsetExists($offset) {
		return isset($this->content[$offset]);
	}

	public function offsetGet($offset) {
		return $this->content[$offset];
	}

	public function offsetSet($offset, $value) {
		//debug('offsetSet', $offset);
		if (is_null($offset)) {
			$this->content[] = $value;
		} else {
			$this->content[$offset] = $value;
		}
	}

	public function add($value) {
		$this->content[] = $value;
	}

	public function addSub($key, $value) {
		$this->content[$key][] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->content[$offset]);
	}

}
