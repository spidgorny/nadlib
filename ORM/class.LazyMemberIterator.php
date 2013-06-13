<?php

class LazyMemberIterator extends IteratorIterator {

	/**
	 * @var string
	 */
	var $class;

	/**
	 * @param array $array
	 * @param int $flags
	 * @param string $class
	 */
	public function __construct($array = array(), $flags = 0, $class) {
		//debug($array);
		parent::__construct($array, $flags);
		$this->class = $class;
	}

	/**
	 * @param string $index
	 * @return OODBase
	 */
	function offsetGet($index) {
		$array = parent::offsetGet($index);
		//debug($array);
		$obj = new $this->class($array);
		//debug($obj);
		return $obj;
	}

	public function current() {
		$array = parent::current();
		$obj = new $this->class($array);
		//debug($array, $obj);
		return $obj;
	}

}
