<?php

class ArrayIteratorPlus implements Iterator, Countable {
	protected $data = array();
	protected $position = 0;

    function current() {
        return $this->data[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function rewind() {
        $this->position = 0;
    }

    function valid() {
        return isset($this->data[$this->position]);
    }
	
	function count() {
		return sizeof($this->data);
	}

}
