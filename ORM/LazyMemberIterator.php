<?php

class LazyMemberIterator extends IteratorIterator implements Countable
{

	/**
	 * @var string
	 */
	public $class;

	/**
	 * Is set by getLazyMemberIterator()
	 * @var int
	 */
	public $count;

	/**
	 * @param Traversable $iterator $array
	 * @param string $class
	 */
	public function __construct(Traversable $iterator, $class, protected DBInterface $db)
	{
		//echo __METHOD__, BR;
		//debug($iterator, sizeof($iterator));
		parent::__construct($iterator);
		$this->class = $class;
	}

	public function count(): int
	{
		//echo __METHOD__, BR;
		/** @var DatabaseResultIteratorAssoc $iterator */
		$iterator = $this->getInnerIterator();
		return $iterator->count();
	}

//	public function valid() {
//		return !!$this->current();
//	}
//

	public function rewind(): void
	{
		//echo __METHOD__, BR;
		/** @var DatabaseResultIteratorAssoc $iterator */
		$iterator = $this->getInnerIterator();
		$iterator->rewind();
	}

	public function next(): void
	{
		//echo __METHOD__, BR;
		//return $this->getInnerIterator()->next();
		parent::next();
	}

	/**
	 * This was fucking missing(!) without any warnings
	 */
	public function valid(): bool
	{
		/** @var DatabaseResultIteratorAssoc $iterator */
		$iterator = $this->getInnerIterator();
		$valid = $iterator->valid();
		$current = $this->getInnerIterator()->current();
		if (!$current) {
			//debug($current);
			//echo __METHOD__, ': ', $valid, ' - ', $current['title'], BR;
			$valid = false;
		}

		return $valid;
	}

	/**
	 * @return mixed|null
	 */
	public function current(): mixed
	{
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
			return new $this->class($array, $this->db);
		}

		return null;
	}

}
