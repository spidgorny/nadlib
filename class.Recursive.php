<?php

class Recursive {

	protected $value;

	protected $elements = array();

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

}