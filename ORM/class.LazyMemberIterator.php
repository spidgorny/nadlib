<?php

class LazyMemberIterator extends IteratorIterator implements Countable {

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
	 * @param Traversable $iterator $array
	 * @param string      $class
	 */
	public function __construct(Traversable $iterator, $class) {
		//debug($iterator, sizeof($iterator));
		parent::__construct($iterator);
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
	public function current() {
		$inner = $this->getInnerIterator();
		//debug($inner);
		//$array = parent::current();
		$array = $inner->current();
		//debug($array);
		if ($array) {
			$obj = new $this->class($array);
			return $obj;
		} else {
			return NULL;
		}
	}

//	public function valid() {
//		return !!$this->current();
//	}
//
	function count() {
		return $this->getInnerIterator()->count();
	}

	function rewind() {
		$this->getInnerIterator()->rewind();
	}

	function next() {
		//return $this->getInnerIterator()->next();
		return parent::next();
	}

}
