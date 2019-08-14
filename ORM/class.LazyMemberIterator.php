<?php

class LazyMemberIterator extends IteratorIterator
{

	/**
	 * @var string
	 */
	var $class;

	/**
	 * Is set by getLazyMemberIterator()
	 * @var
	 */
	var $count;

	/**
	 * @param array $array
	 * @param int $flags
	 * @param string $class
	 */
	public function __construct($array = array(), $flags = 0, $class)
	{
		//debug($array, $array->count());
		parent::__construct($array, $flags);
		$this->class = $class;
	}

	/**
	 * Not used by the Iterator
	 * @param string $index
	 * @return OODBase
	 */
	/*function offsetGet($index) {
		$array = parent::offsetGet($index);
		if ($array) {
			$obj = new $this->class($array);
			debug($array, $obj);
			return $obj;
		} else {
			return NULL;
		}
	}*/

	/**
	 * @return mixed|null
	 */
	public function current()
	{
		$array = parent::current();
		if ($array) {
			$obj = new $this->class($array);
			//debug($array, $obj);
			return $obj;
		} else {
			return NULL;
		}
	}

	public function valid()
	{
		return !!$this->current();
	}

	function count()
	{
		return $this->count;
	}

}
