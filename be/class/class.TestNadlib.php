<?php

class TestNadlib extends uTestBase {

	public static $public = true;

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
		$b = AP($a)->typoscript();
		//debug($b);
		return $this->assertEqual($b, array(
			'a' => 'b',
			'c.d' => 'e',
			'c.f.g' => 'h',
		));
	}

}
