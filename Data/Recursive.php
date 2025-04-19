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

	/**
     * @var mixed[]
     */
    public $elements = [];

	public function __construct($value, array $elements = [])
	{
		$this->value = $value;
		$this->elements = $elements;
	}

	public function setValue($value): void
	{
		$this->value = $value;
	}

	public function __toString(): string
	{
		return $this->value;
	}

	public function getChildren()
	{
		return $this->elements;
	}

	/**
     * @return Recursive
     */
    public function findPath(array $path)
	{
		//debug($path);
		if ($path !== []) {
			$current = array_shift($path);
			/** @var Recursive $find */
			$find = ifsetor($this->elements[$current]);
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
     */
    public function eachRecursive($callback): static
	{
		foreach ($this->elements as $i => &$el) {
			$el = $el instanceof Recursive ? $el->eachRecursive($callback) : call_user_func($callback, $el, $i);
		}

		unset($el);
		return $this;
	}

	/**
     * Callback = function ($value, [$index]) {}
     *
     * @param callable $callback
     * @param int $level
     */
    public function eachRecursiveKey($callback, $level = 0): static
	{
		$new = [];
		foreach ($this->elements as $i => $el) {
			$val = $el instanceof Recursive ? $el->eachRecursiveKey($callback, $level + 1) : null;

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
