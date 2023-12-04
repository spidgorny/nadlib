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

	public $data = [];

	public function rewind()
	{
		reset($this->data);
	}

	public function current()
	{
		return current($this->data);
	}

	public function key()
	{
		return key($this->data);
	}

	public function next()
	{
		return next($this->data);
	}

	public function valid()
	{
		return $this->key() !== null;
	}

	public function count()
	{
		return sizeof($this->data);
	}

}
