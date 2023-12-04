<?php

class LazyObject extends ArrayObject
{

}

class LazyMemberIteratorTest extends PHPUnit\Framework\TestCase
{

	/**
	 * @var ArrayIterator
	 */
	protected $list;

	/**
	 * @var LazyMemberIterator|Iterator
	 */
	protected $sut;

	public function setUp()
	{
		$set = [];
		for ($i = 0; $i < 10; $i++) {
			$set[] = range(10 * $i + 0, 10 * $i + 4);
		}
		$this->list = new ArrayIterator($set);
		$this->sut = new LazyMemberIterator(
			$this->list,
			LazyObject::class
		);
	}

	public function test_count()
	{
		$this->assertEquals(10, $this->sut->count());
	}

	public function test_current_function()
	{
		$list = new ArrayIterator(range(0, 4));
		$curM = $list->current();
		$curF = current($list);
		//debug($curF);
		$this->assertEquals($curM, $curF);
	}

	public function test_current_function_this_list()
	{
		$this->list->rewind();
		$curM = $this->list->current();
		$curF = current($this->list);
//		llog($this->list->getArrayCopy());
//		llog($curM, $curF);
		if (phpversion() >= '7.4') {
			$this->assertEquals(false, $curF);
		} else {
			$this->assertEquals($curM, $curF);
		}
	}

	/**
	 * @ignore
	 */
	public function no_test_current_function_on_lazy()
	{
		$this->sut->rewind();
		$curM = $this->sut->current();
		$curF = current($this->sut);
		//debug($this->sut, $curM, $curF);
		$this->assertEquals($curM, $curF);
	}

	public function test_foreach_normal()
	{
		foreach ($this->list as $i => $el) {
			$this->assertEquals($el, range(10 * $i + 0, 10 * $i + 4));
		}
	}

	public function test_foreach_lazy()
	{
//		debug(range(0, 4));
		$this->sut->rewind();
		//echo 'Count: ', $this->sut->count(), BR;
		/** @var LazyObject $el */
		foreach ($this->sut as $i => $el) {
			//debug($el);
			$this->assertEquals(
				$el->getArrayCopy(),
				range($i * 10, $i * 10 + 4)
			);
		}
	}

}
