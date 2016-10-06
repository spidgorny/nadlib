<?php

class IteratorImplementation {

	var $data = array();

	private $position = 0;

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->data[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->data[$this->position]);
    }

}
