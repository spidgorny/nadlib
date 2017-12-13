<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 27.04.2017
 * Time: 16:55
 */

namespace nadlib\Controller;

use Controller;

class ControllerTest extends \PHPUnit_Framework_TestCase {

	function test_makeLinkSimple() {
		$c = new \AppController4Test();
		$c->useRouter = false;
		$link = $c->makeURL(['a' => 'b']);
		$this->assertEquals('?a=b', $link.'');
	}

	function test_makeLinkSimpleWithPrefix() {
		$c = new \AppController4Test();
		$c->useRouter = false;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$this->assertEquals('prefix?a=b', $link.'');
	}

	function test_makeLinkRouter() {
		$c = new \AppController4Test();
		$c->useRouter = true;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$this->assertEquals('prefix?a=b', $link.'');
	}

	function test_makeLinkRouterWithPrefix() {
		$c = new \AppController4Test();
		$c->useRouter = true;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$this->assertEquals('prefix?a=b', $link.'');
	}

	function test_makeLinkCSimple() {
		$c = new \AppController4Test();
		$c->useRouter = false;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$this->assertEquals('?a=b&c=Controller', $link.'');
	}

	function test_makeLinkCRouter() {
		$c = new \AppController4Test();
		$c->useRouter = true;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$this->assertEquals('Controller?a=b', $link.'');
	}

}
