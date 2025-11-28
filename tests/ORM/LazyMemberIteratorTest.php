<?php

namespace ORM;

use ArrayIterator;
use ArrayObject;
use LazyMemberIterator;
use PHPUnit\Framework\TestCase;

class LazyObject extends ArrayObject
{
}


class LazyMemberIteratorTest extends TestCase
{

	/**
	 * @var ArrayIterator
	 */
	protected $list;

	/**
	 * @var LazyMemberIterator
	 */
	protected $sut;

	/**
	 * @var \DBInterface
	 */
	protected $db;

	public function test_count(): void
	{
		static::assertEquals(10, $this->sut->count());
	}

	public function test_current_function(): void
	{
		$list = new ArrayIterator(range(0, 4));
		$curM = $list->current();
		//debug($curF);
		static::assertEquals(0, $curM);
	}

	public function test_current_function_this_list(): void
	{
		$this->list->rewind();
		$curM = $this->list->current();
//		llog($this->list->getArrayCopy());
//		llog($curM, $curF);
		static::assertEquals(0, $curM);
	}

	/**
	 * @ignore
	 */
	public function no_test_current_function_on_lazy(): void
	{
		$this->sut->rewind();
		$curM = $this->sut->current();
		$curF = current($this->sut);
		//debug($this->sut, $curM, $curF);
		static::assertEquals($curM, $curF);
	}

	public function test_foreach_normal(): void
	{
		foreach ($this->list as $i => $el) {
			static::assertEquals($el, range(10 * $i + 0, 10 * $i + 4));
		}
	}

	public function test_foreach_lazy(): void
	{
//		debug(range(0, 4));
		$this->sut->rewind();
		//echo 'Count: ', $this->sut->count(), BR;
		/** @var LazyObject $el */
		foreach ($this->sut as $i => $el) {
			//debug($el);
			static::assertEquals(
				$el->getArrayCopy(),
				range($i * 10, $i * 10 + 4)
			);
		}
	}

	protected function setUp(): void
	{
		parent::setUp();
		$set = [];
		for ($i = 0; $i < 10; $i++) {
			$set[] = range(10 * $i, 10 * $i + 4);
		}

		$this->list = new ArrayIterator($set);
		$this->sut = new LazyMemberIterator($this->list, LazyObject::class, $this->db);
	}

}
