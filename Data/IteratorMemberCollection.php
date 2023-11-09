<?php

abstract class IteratorMemberCollection extends CollectionMock implements Iterator, Countable
{

	public function __construct($pid = null, /*array/SQLWhere*/ $where = [], $order = '')
	{
		parent::__construct($pid, $where, $order);
		//$this->objectify();   // may cause data retrieval without $where
	}

	public function rewind()
	{
		$this->objectify();
		reset($this->members);
	}

	public function current()
	{
		return current($this->members);
	}

	public function key()
	{
		return key($this->members);
	}

	public function next()
	{
		return next($this->members);
	}

	public function valid()
	{
		return $this->key() !== null;
	}

	public function count()
	{
		$this->objectify();
		return sizeof($this->members);
	}

}
