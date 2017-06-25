<?php

class ArrayPlusReference extends ArrayPlus {

	function __construct(array &$a = array()) {
		$this->data =& $a;
	}

	static function create(array &$data = array()) {
		$self = new self($data);
		return $self;
	}

	function &getData() {
		return $this->data;
	}

}

function APR(array &$a = array()) {
	return ArrayPlusReference::create($a);
}
