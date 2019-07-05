<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 27.04.2017
 * Time: 16:55
 */

namespace nadlib\Controller;

class ControllerTest extends \PHPUnit\Framework\TestCase
{

	protected $globalPrefix = '/some/folder';

	public function test_getLocation()
	{
		$request = \Request::getInstance();

		$byRequest = $request->getDocumentRootByRequest();
		$this->assertEquals('/', $byRequest);

		$byDocRoot = $request->getDocumentRootByDocRoot();
		$this->assertEquals(null, $byDocRoot);

		$docRoot = $request->getDocumentRoot();
		$this->assertEquals('/', $docRoot);

		$location = $request->getLocation();
		$this->assertEquals('http://mock.request.tld/some/folder/', $location);
	}

	public function test_makeLinkSimple()
	{
		$c = new \AppController4Test();
		$c->linker->useRouter = false;
		$link = $c->makeURL(['a' => 'b']);
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix.'/?a=b', $link . '');
	}

	public function test_makeLinkSimpleWithPrefix()
	{
		$c = new \AppController4Test();
		$c->linker->useRouter = false;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix.'/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouter()
	{
		$c = new \AppController4Test();
		$c->linker->useRouter = true;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix.'/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouterWithPrefix()
	{
		$c = new \AppController4Test();
		$c->linker->useRouter = true;
		$link = $c->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix.'/prefix?a=b', $link . '');
	}

	public function test_makeLinkCSimple()
	{
		$c = new \AppController4Test();
		$c->linker->useRouter = false;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix.'/?a=b&c=Controller', $link . '');
	}

	public function test_makeLinkCRouter()
	{
		$c = new \AppController4Test();
		$c->linker->useRouter = true;
		$link = $c->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		$this->assertEquals($this->globalPrefix.'/Controller?a=b', $link . '');
	}

}
