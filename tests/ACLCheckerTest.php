<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 25.02.2016
 * Time: 19:07
 */

namespace tests;


use ACLChecker;
use AppController;
use NOEUser;
use PHPUnit_Framework_TestCase;

class ACLCheckerTest extends PHPUnit_Framework_TestCase
{

	function test_compareACL()
	{
		$controller = new AppController();
		$controller->user = NULL;
		$ac = new ACLChecker($controller);
		$okNothing = $ac->compareACL(NULL);
		$okNull = $ac->compareACL('null');
		$okUser = $ac->compareACL('user');
		debug(gettype2($controller->user), $okNothing, $okNull, $okUser);
	}

	function test_compareACLUser()
	{
		$controller = new AppController();
		$controller->user = new NOEUser();
		$controller->user->id = 1;
		$ac = new ACLChecker($controller);
		$okNothing = $ac->compareACL(NULL);
		$okNull = $ac->compareACL('null');
		$okUser = $ac->compareACL('user');
		debug(gettype2($controller->user), $okNothing, $okNull, $okUser);
	}

}
