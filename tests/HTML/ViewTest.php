<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.12.2015
 * Time: 21:20
 */
class ViewTest extends PHPUnit_Framework_TestCase {

	function test_cleanComment() {
		if (class_exists('HTMLPurifier_Config')) {
			$v = new View('whatever');
			$clean = $v->cleanComment('Some shit');
			$this->assertNotEmpty($clean);
		}
	}

}
