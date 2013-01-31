<?php

class TestNadlib extends uTestBase {

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
		$b = AP($a)->typoscript()->getData();
		debug($b);
	}

}
