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
		//echo __METHOD__, BR;
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
		//echo __METHOD__, BR;
		/** @var DatabaseResultIteratorAssoc $inner */
		$inner = $this->getInnerIterator();
		//echo gettype2($inner), BR;
		//debug($inner);
		//$array = parent::current();
		$array = $inner->current();
		//debug($array);
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
		//echo __METHOD__, BR;
		/** @var DatabaseResultIteratorAssoc $iterator */
		$iterator = $this->getInnerIterator();
		return $iterator->count();
	}

	function rewind() {
		//echo __METHOD__, BR;
		/** @var DatabaseResultIteratorAssoc $iterator */
		$iterator = $this->getInnerIterator();
		$iterator->rewind();
	}

	function next() {
		//echo __METHOD__, BR;
		//return $this->getInnerIterator()->next();
		parent::next();
	}

	/**
	 * This was fucking missing(!) without any warnings
	 * @return bool
	 */
	function valid() {
		//echo __METHOD__, BR;
		/** @var DatabaseResultIteratorAssoc $iterator */
		$iterator = $this->getInnerIterator();
		return $iterator->valid();
	}

}
