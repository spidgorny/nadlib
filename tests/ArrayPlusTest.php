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

	function test_typoscript() {
		$a = array(
			'a' => 'b',
			'c' => array(
				'd' => 'e',
				'f' => array(
					'g' => 'h',
				),
			),
		);
		$b = ArrayPlus::create($a)->typoscript();
		//debug($b);
		$this->assertEquals(array(
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h',
		), $b);
	}

    function test_unset() {
        unset($this->ai[1]);
        $this->assertEquals(array(
            0 => 'a',
            'slawa' => 'test'
        ), $this->ai->getData());
    }

}
