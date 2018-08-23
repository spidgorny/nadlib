<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 27.04.2017
 * Time: 16:55
 */

namespace nadlib\Controller;

class ControllerTest extends \PHPUnit_Framework_TestCase
{

	public function test_makeLinkSimple()
	{
		$c = new \AppController4Test();
		$c->useRouter = false;
		$link = $c->makeURL(['a' => 'b']);
		$link->setHost(null);
		$this->assertEquals('?a=b', $link . '');
	}

	public function test_makeLinkSimpleWithPrefix()
	{
		$c = new \AppController4Test();
		$c->useRouter = false;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals('/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouter()
	{
		$c = new \AppController4Test();
		$c->useRouter = true;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals('/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouterWithPrefix()
	{
		$c = new \AppController4Test();
		$c->useRouter = true;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals('/prefix?a=b', $link . '');
	}

	public function test_makeLinkCSimple()
	{
		$c = new \AppController4Test();
		$c->useRouter = false;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		$this->assertEquals('?a=b&c=Controller', $link . '');
	}

	public function test_makeLinkCRouter()
	{
		$c = new \AppController4Test();
		$c->useRouter = true;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		$this->assertEquals('/Controller?a=b', $link . '');
	}

}
