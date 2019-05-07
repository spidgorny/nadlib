<?php

class IteratorArrayAccessTest extends ArrayIteratorPlusTest
{

	/**
	 * @var ArrayIterator $ai
	 */
	protected $ai;

	function setUp()
	{
		$this->ai = new ArrayIterator(array(
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		));
	}

}
