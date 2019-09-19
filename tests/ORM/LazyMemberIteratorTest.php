<?php

class LazyObject extends ArrayObject
{

}

class LazyMemberIteratorTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var ArrayIterator
	 */
	public $list;

	/**
	 * @var LazyMemberIterator|Iterator
	 */
	public $sut;

	function setUp()
	{
		$set = [];
		for ($i = 0; $i < 10; $i++) {
			$set[] = range(10 * $i + 0, 10 * $i + 4);
		}
		$this->list = new ArrayIterator($set);
		$this->sut = new LazyMemberIterator(
			$this->list, LazyObject::class
		);
	}

	function test_count()
	{
		$this->assertEquals(10, $this->sut->count());
	}

	function test_current_function()
	{
		$list = new ArrayIterator(range(0, 4));
		$curM = $list->current();
		$curF = current($list);
		//debug($curF);
		$this->assertEquals($curM, $curF);
	}

	function test_current_function_this_list()
	{
		$curM = $this->list->current();
		$curF = current($this->list);
		//debug($curM, $curF);
		$this->assertEquals($curM, $curF);
	}

	/**
	 * @ignore
	 */
	function no_test_current_function_on_lazy()
	{
		$this->sut->rewind();
		$curM = $this->sut->current();
		$curF = current($this->sut);
		//debug($this->sut, $curM, $curF);
		$this->assertEquals($curM, $curF);
	}

	function test_foreach_normal()
	{
		foreach ($this->list as $i => $el) {
			$this->assertEquals($el, range(10 * $i + 0, 10 * $i + 4));
		}
	}

	function test_foreach_lazy()
	{
		$this->sut->rewind();
		//echo 'Count: ', $this->sut->count(), BR;
		foreach ($this->sut as $el) {
			//debug($el);
			$this->assertEquals($el, range(0, 4));
		}
	}

}
