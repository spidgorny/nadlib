<?php

/**
 * Class MergedContent
 * This behaves like a string.
 * But you can also access parts of the string separately.
 * Useful when some HTML content is generated in different parts.
 * Sometimes you need to output the whole content,
 * sometimes you only need a part of it.
 * @see http://php.net/manual/en/class.arrayaccess.php#113865
 */
class MergedContent implements ArrayAccess
{

	public $content = [];

	public function __construct(array $parts = [])
	{
		$this->content = $parts;
	}

	protected static function walkMerge($value, $key, &$combined = '')
	{
		$combined .= $value . "\n";
	}

	protected static function walkMergeArray($value, $key, &$combined)
	{
		$combined[] = $value;
	}

	public function __toString()
	{
//		debug_pre_print_backtrace();
		return $this->getContent();
	}

	public function getContent()
	{
		return $this->mergeStringArrayRecursive($this->content);
	}

	/**
	 * @param string|string[]|object $render
	 * @return string
	 */
	public static function mergeStringArrayRecursive($render)
	{
		TaylorProfiler::start(__METHOD__);
		if (is_array($render)) {
			$arrayOfObjects = array_flatten($render);
			$sureStrings = self::stringify($arrayOfObjects);
			$combined = implode('', $sureStrings);
			$render = $combined;
		} elseif (is_object($render)) {
			try {
				$render .= '';
			} catch (ErrorException $e) {
				$render = '?[' . get_class($render) . ']?';
			}
		} else {
			$render .= '';    // just in case
		}
		TaylorProfiler::stop(__METHOD__);
		return $render;
	}

	public static function stringify(array $objects)
	{
		foreach ($objects as &$element) {
//			$debug = '-= ' . typ($element) . ' =-' . BR;
			//echo $debug;
			//$content .= $debug;
			$element = is_object($element)
				? $element . ''
				: $element;
		}
		return $objects;
	}

	public function offsetExists($offset)
	{
		return isset($this->content[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->content[$offset];
	}

	public function offsetSet($offset, $value)
	{
		//debug('offsetSet', $offset);
		if (is_null($offset)) {
			$this->content[] = $value;
		} else {
			$this->content[$offset] = $value;
		}
	}

	public function add($value)
	{
		$this->content[] = $value;
	}

	public function addSub($key, $value)
	{
		if (isset($this->content[$key]) && is_string($this->content[$key])) {
			$this->content[$key] = [$this->content[$key]];
		}
		$this->content[$key][] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->content[$offset]);
	}

	/**
	 * Get a data by key
	 *
	 * @param string $key The key data to retrieve
	 * @access public
	 * @return mixed
	 */
	public function &__get($key)
	{
		return $this->content[$key];
	}

	/**
	 * Assigns a value to the specified data
	 *
	 * @param string $key The data key to assign the value to
	 * @param mixed $value The value to set
	 * @access public
	 */
	public function __set($key, $value)
	{
		$this->content[$key] = $value;
	}

	/**
	 * Whether or not an data exists by key
	 *
	 * @param string $key An data key to check for
	 * @access public
	 * @return bool
	 * @abstracting ArrayAccess
	 */
	public function __isset($key)
	{
		return isset($this->content[$key]);
	}

	/**
	 * Unsets an data by key
	 *
	 * @param string $key The key to unset
	 * @access public
	 */
	public function __unset($key)
	{
		unset($this->content[$key]);
	}

	public function clear()
	{
		debug('clear');
		$this->content = [];
	}

}
