<?php

abstract class IteratorMemberCollection extends CollectionMock implements Iterator, Countable
{

	function __construct($pid = NULL, /*array/SQLWhere*/ $where = array(), $order = '')
	{
		parent::__construct($pid, $where, $order);
		//$this->objectify();   // may cause data retrieval without $where
	}

	function rewind()
	{
		$this->objectify();
		reset($this->members);
	}

	function current()
	{
		return current($this->members);
	}

	function key()
	{
		return key($this->members);
	}

	function next()
	{
		return next($this->members);
	}

	function valid()
	{
		return $this->key() !== NULL;
	}

	function count()
	{
		$this->objectify();
		return sizeof($this->members);
	}

}
