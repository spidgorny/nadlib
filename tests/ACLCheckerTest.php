<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 25.02.2016
 * Time: 19:07
 */

namespace tests;

class ACLCheckerTest extends \PHPUnit_Framework_TestCase
{

	public function test_compareACL()
	{
		$this->markTestIncomplete(
			'AppController was not found.'
		);
		$controller = new \AppController();
		$controller->user = null;
		$ac = new \ACLChecker($controller);
		$okNothing = $ac->compareACL(null);
		$okNull = $ac->compareACL('null');
		$okUser = $ac->compareACL('user');
		debug(typ($controller->user), $okNothing, $okNull, $okUser);
	}

	public function test_compareACLUser()
	{
		$this->markTestIncomplete(
			'AppController was not found.'
		);
		$controller = new \AppController();
		$controller->user = new \NOEUser();
		$controller->user->id = 1;
		$ac = new \ACLChecker($controller);
		$okNothing = $ac->compareACL(null);
		$okNull = $ac->compareACL('null');
		$okUser = $ac->compareACL('user');
		debug(typ($controller->user), $okNothing, $okNull, $okUser);
	}

}
