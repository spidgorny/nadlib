<?php

namespace spidgorny\nadlib\Data;

/**
 * Class ArrayIteratorPlus - convenient base class for implementing an iterator class which can be used in foreach()
 * Just put your data into $this->data in the constructor. The rest (iteration) will be taken care of.
 */
class ArrayIteratorPlus implements \Iterator {

	/**
	 * @var array - the actual data for iteration
	 */
	protected $data = array();

	/**
	 * Current position of iteration
	 * @var int
	 */
	private $position = 0;

	public $debug = false;

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
		$valid = isset($this->data[$this->position]);
		if ($this->debug) {
			echo __METHOD__.': '.$valid."<br />\n";
		}
        return $valid;
    }

	function getData() {
		return $this->data;
	}

}
