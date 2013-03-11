<?php

class IteratorMemberCollection extends Collection implements Iterator {

	function rewind() {
		$this->objectify();
		reset($this->members);
    }

	function current() {
        return current($this->members);
    }

	function key() {
        return key($this->members);
    }

	function next() {
        return next($this->members);
    }

	function valid() {
        return $this->key() !== NULL;
    }

}
