<?php

/**
 * Class ArrayIteratorPlus - convenient base class for implementing an iterator class which can be used in foreach()
 * Just put your data into $this->data in the constructor. The rest (iteration) will be taken care of.
 *
 * 2014-01-30 It now extends ArrayIterator instead of implementing everything manually
 */
//class ArrayIteratorPlus implements Iterator, Countable {
class ArrayIteratorPlus extends ArrayIterator implements Iterator, Countable {

	/**
	 * @var array - the actual data for iteration
	 */
	protected $data = array();

	/**
	 * Current position of iteration
	 * @var int
	 */
	protected $position = 0;

/*	public $debug = true;

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
			debug($this->position, $valid, $this->data);
		}
        return $valid;
    }

	function count() {
		return sizeof($this->data);
	}

	function getData() {
		return $this->data;
	}
*/
}
