<?php

class Recursive {

	protected $value;

	public $elements = array();

	function __construct($value, array $elements = array()) {
		$this->value = $value;
		$this->elements = $elements;
	}

	function __toString() {
		return $this->value;
	}

	function getChildren() {
		return $this->elements;
	}

	/**
	 * @param array $path
	 * @return Recursive
	 */
	function findPath(array $path) {
		//debug($path);
		if ($path) {
			$current = array_shift($path);
			$find = $this->elements[$current];
			if ($find && $path) {
				$find = $find->findPath($path);
			}
		} else {
			$find = $this;	// Recursive
		}
		return $find;
	}

}