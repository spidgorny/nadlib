<?php

/**
 * TODO: move to nadlib and find IteratorMembersCollection
 * This is a dead end, use Collection->getIterator()
 * @deprecated Collection implements IteratorAggregate already
 * Fatal error: Class IteratorCollection cannot implement both Iterator and IteratorAggregate at the same time
 */
class IteratorCollection /*extends Collection*/
	implements Iterator, Countable
{

	public $data = array();

	function rewind()
	{
		reset($this->data);
	}

	function current()
	{
		return current($this->data);
	}

	function key()
	{
		return key($this->data);
	}

	function next()
	{
		return next($this->data);
	}

	function valid()
	{
		return $this->key() !== NULL;
	}

	function count()
	{
		return sizeof($this->data);
	}

}
