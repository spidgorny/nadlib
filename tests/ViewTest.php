<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.12.2015
 * Time: 21:20
 */
class ViewTest extends PHPUnit_Framework_TestCase {

	function test_cleanComment() {
		$v = new View();
		$clean = $v->cleanComment('Some shit');
		$this->assertNotEmpty($clean);
	}

}
