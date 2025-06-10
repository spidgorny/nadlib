<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 25.02.2016
 * Time: 19:07
 */

// @todo: move to ORS
use AppDev\OnlineRequestSystem\MyTestCase;

class ACLCheckerTest extends MyTestCase
{

	public function test_compareACL(): void
	{
		$this->markTestSkipped(
			'AppController was not found.'
		);
		$controller = new TestController();
		$controller->user = null;

		$ac = new ACLChecker($controller);
		$ac->compareACL(null);
		$ac->compareACL('null');
		$ac->compareACL('user');
//		debug(typ($controller->user), $okNothing, $okNull, $okUser);
	}

	public function test_compareACLUser(): void
	{
		$this->markTestSkipped(
			'AppController was not found.'
		);
		$controller = new TestController();
		$controller->user = new NOEUser();
		$controller->user->id = 1;

		$ac = new ACLChecker($controller);
		$ac->compareACL(null);
		$ac->compareACL('null');
		$ac->compareACL('user');
//		debug(typ($controller->user), $okNothing, $okNull, $okUser);
	}

}
