<?php

/**
 * Class Recursive - a general class to store hierarhical information.
 * Can be a replacement for TypoScript style of structure (same key with "." in the end contains sub-nodes).
 * Used by {@link Menu} to display the menu item itself as well as contain sub-menus.
 */

class Recursive
{

	/**
	 * @var string
	 */
	public $value;

	public $elements = array();

	function __construct($value, array $elements = array())
	{
		$this->value = $value;
		$this->elements = $elements;
	}

	function setValue($value)
	{
		$this->value = $value;
	}

	function __toString()
	{
		return strip_tags(strval($this->value));
	}

	function getChildren()
	{
		return $this->elements;
	}

	/**
	 * @param array $path
	 * @return Recursive
	 */
	function findPath(array $path)
	{
		//debug($path);
		if ($path) {
			$current = array_shift($path);
			/** @var Recursive $find */
			$find = $this->elements[$current];
			if ($find && $path) {
				$find = $find->findPath($path);
			}
		} else {
			$find = $this;    // Recursive
		}
		return $find;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 * NOT TESTED
	 * @param callable $callback
	 * @return Recursive
	 */
	function eachRecursive($callback)
	{
		foreach ($this->elements as $i => &$el) {
			if ($el instanceof Recursive) {
				$el = $el->eachRecursive($callback);
			} else {
				$el = call_user_func($callback, $el, $i);
			}
		}
		unset($el);
		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param callable $callback
	 * @param int $level
	 * @return Recursive
	 */
	function eachRecursiveKey($callback, $level = 0)
	{
		$new = array();
		foreach ($this->elements as $i => $el) {
			if ($el instanceof Recursive) {
				$val = $el->eachRecursiveKey($callback, $level + 1);
			} else {
				$val = NULL;
			}
			$res = call_user_func($callback, $val, $i);
			if (!is_null($res)) {
				list($val, $key) = $res;
				$new[$key] = $val;
			} else {
				// unset
			}
			unset($el);
		}
		//debug(__METHOD__, $level, $this->elements, $new);
		$this->elements = $new;
		return $this;
	}

	public function getName()
	{
		return $this->value;
	}

}
