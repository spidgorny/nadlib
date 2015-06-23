<?php

/**
 * TODO: move to nadlib and find IteratorMembersCollection
 * @deprecated Collection implements IteratorAggregate already
 */
class IteratorCollection /*extends Collection*/ implements Iterator, Countable {

	var $data = array();

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

	function count() {
		return sizeof($this->data);
	}

}
