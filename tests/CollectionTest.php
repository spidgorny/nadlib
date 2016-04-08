<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 02.02.2016
 * Time: 16:42
 */
class CollectionTest extends PHPUnit_Framework_TestCase {


	protected function setUp()
	{
		$this->markTestSkipped(
			'This test in a wrong place after discussion with Slava.'
		);
	}

	function test_lazyMemeberIterator() {
		$rc = new RequestCollection();
		$rc->orderBy = 'ORDER BY ctime DESC LIMIT 10';
		$iterator = $rc->getLazyMemberIterator('ORSRequest');
		$current = $iterator->current();
		debug($current);
	}

}
