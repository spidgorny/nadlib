<?php

require_once 'tests/IteratorArrayAccessTest.php';

class ArrayPlusTest extends IteratorArrayAccessTest {

	/**
	 * @var ArrayPlus $ai
	 */
	protected $ai;

	function setUp() {
		$this->ai = new ArrayPlus(array(
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		));
	}

}
