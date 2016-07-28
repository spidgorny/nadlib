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

	function getContent() {
		return $this->mergeStringArrayRecursive($this->content);
	}

	function __toString() {
//		debug_pre_print_backtrace();
		return $this->getContent();
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
	 * @return mixed
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

	public function clear() {
		$this->content = array();
	}

	/**
	 * @param string|string[] $render
	 * @return string
	 */
	static function mergeStringArrayRecursive($render) {
		TaylorProfiler::start(__METHOD__);
		if (is_array($render)) {
			$combined = '';
			/*array_walk_recursive($render,
				array('IndexBase', 'walkMerge'),
				$combined); // must have &
			*/

			//$combined = array_merge_recursive($render);
			//$combined = implode('', $combined);

			$combinedA = new ArrayObject();
			array_walk_recursive($render, array(__CLASS__, 'walkMergeArray'), $combinedA);
			$arrayOfObjects = $combinedA->getArrayCopy();
			$sureStrings = self::stringify($arrayOfObjects);
			$combined = implode('', $sureStrings);
			$render = $combined;
		} elseif (is_object($render)) {
			try {
				$render = $render . '';
			} catch (ErrorException $e) {
				debug_pre_print_backtrace();
				debug('Object of class ', get_class($render), 'could not be converted to string');
				debug($render);
			}
		} else {
			$render = $render.'';	// just in case
		}
		TaylorProfiler::stop(__METHOD__);
		return $render;
	}

	static function stringify(array $objects) {
		foreach ($objects as &$element) {
			$debug = '-= '.gettype2($element).' =-'.BR;
			//echo $debug;
			//$content .= $debug;
			$element = is_object($element)
				? $element.''
				: $element;
		}
		return $objects;
	}

	protected static function walkMerge($value, $key, &$combined = '') {
		$combined .= $value."\n";
	}

	protected static function walkMergeArray($value, $key, $combined) {
		$combined[] = $value;
	}

}
