<?php

class IteratorCollection extends Collection implements Iterator {

	function rewind() {
		reset($this->data);
    }

	function current() {
        return current($this->data);
    }

	function key() {
        return key($this->data);
    }

	function next() {
        return next($this->data);
    }

	function valid() {
        return $this->key() !== NULL;
    }

}