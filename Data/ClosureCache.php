<?php

class ClosureCache {

	var $function;

	var $result;

	function __construct(callable $function) {
		$this->function = $function;
	}

	function get() {
		if (!$this->result) {
			$this->result = call_user_func($this->function);
		}
		return $this->result;
	}

	function __toString() {
		return $this->get().'';
	}

}
