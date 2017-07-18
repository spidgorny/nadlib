<?php

class ArrayIteratorPlusTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var ArrayIteratorPlus $ai
	 */
	protected $ai;

	function setUp() {
		$this->ai = new ArrayIteratorPlus(array(
			0 => 'a',
			1 => 'b',
			'slawa' => 'test',
		));
	}

	function test_ArrayIterator_foreach() {
		$ai = new ArrayIterator($this->ai->getArrayCopy());
		$content = '';
		foreach ($ai as $i => $a) {
			$content .= $a;
		}
		$this->assertEquals('abtest', $content);
	}

	function test_ArrayIteratorPlus_foreach() {
		$content = '';
		foreach ($this->ai as $i => $a) {
			$content .= $a;
		}
		$this->assertEquals('abtest', $content);
	}

}
