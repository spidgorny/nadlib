<?php

class IteratorArrayAccessTest extends ArrayIteratorPlusTest
{

	/**
	 * @var IteratorArrayAccess $ai
	 */
	protected $ai;

	function setUp()
	{
		$this->ai = new IteratorArrayAccess(array(
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		));
	}

}
