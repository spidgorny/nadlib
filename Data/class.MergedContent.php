<?php

/**
 * Class MergedContent
 * This behaves like a string.
 * But you can also access parts of the string separately.
 * Useful when some HTML content is generated in different parts.
 * Sometime you need to output the whole content,
 * sometimes you only need a part of it.
 * @see http://php.net/manual/en/class.arrayaccess.php#113865
 */
class MergedContent implements ArrayAccess {

	var $content = array();

	function __construct(array $parts = array()) {
		$this->content = $parts;
	}

	function __toString() {
		return IndexBase::mergeStringArrayRecursive($this->content);
	}

	static function mergeStringArrayRecursive($s) {
		return IndexBase::mergeStringArrayRecursive($s);
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

	/**
	 * Get a data by key
	 *
	 * @param string $key The key data to retrieve
	 * @access public
	 */
	public function &__get ($key) {
		return $this->content[$key];
	}

	/**
	 * Assigns a value to the specified data
	 *
	 * @param string $key The data key to assign the value to
	 * @param mixed  $value The value to set
	 * @access public
	 */
	public function __set($key,$value) {
		$this->content[$key] = $value;
	}

	/**
	 * Whether or not an data exists by key
	 *
	 * @param string $key An data key to check for
	 * @access public
	 * @return boolean
	 * @abstracting ArrayAccess
	 */
	public function __isset($key) {
		return isset($this->content[$key]);
	}

	/**
	 * Unsets an data by key
	 *
	 * @param string $key The key to unset
	 * @access public
	 */
	public function __unset($key) {
		unset($this->content[$key]);
	}

}
