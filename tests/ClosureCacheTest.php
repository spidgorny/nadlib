<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.06.2016
 * Time: 16:42
 */
class ClosureCacheTest extends PHPUnit_Framework_TestCase {

	function test_it() {
		$cc = new ClosureCache(
			function () {
				return rand(0, PHP_INT_MAX);
			}
		);
		$first = $cc->get();
		$second = $cc->get();
		$this->assertEquals($first, $second);
	}

}
