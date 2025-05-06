<?php
/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 27.04.2017
 * Time: 16:55
 */

namespace nadlib\Controller;

use App\Tests\RisTestCase;
use AppController4Test;
use nadlib\Test\MockRequest;

class ControllerTest extends RisTestCase
{

	protected string $location = 'http://mock.request.tld';

	protected string $globalPrefix = '/some/folder';

	/**
	 * @var MockRequest
	 */
	protected MockRequest $request;

	protected function setUp(): void
	{
		$this->request = new MockRequest();
		self::markTestSkipped('PG dependent');
	}

	public function test_getLocation(): void
	{
		$byRequest = MockRequest::getDocumentRootByRequest();
		static::assertEquals('/', $byRequest);

		$byDocRoot = MockRequest::getDocumentRootByDocRoot();
		static::assertEquals(null, $byDocRoot);

		$docRoot = MockRequest::getDocumentRoot();
		static::assertEquals('/', $docRoot);

		$location = MockRequest::getLocation();
		static::assertEquals($this->location . $this->globalPrefix . '/', $location . '');
	}

	public function test_makeLinkSimple(): void
	{
		$c = new AppController4Test();
		$c->linker->useRouter = false;
		$c->request = $this->request;

		$link = $c->linker->makeURL(['a' => 'b']);
		$link->setHost(null);
		static::assertEquals($this->globalPrefix . '/?a=b', $link . '');
	}

	public function test_makeLinkSimpleWithPrefix(): void
	{
		$c = new AppController4Test();
		$c->linker->useRouter = false;
		$c->request = $this->request;

		$link = $c->linker->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		static::assertEquals($this->globalPrefix . '/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouter(): void
	{
		$c = new AppController4Test();
		$c->linker->useRouter = true;
		$c->request = $this->request;

		$link = $c->linker->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		static::assertEquals($this->globalPrefix . '/prefix?a=b', $link . '');
	}

	public function test_makeLinkRouterWithPrefix(): void
	{
		$c = new AppController4Test();
		$c->linker->useRouter = true;
		$c->request = $this->request;

		$link = $c->linker->makeURL(['a' => 'b'], 'prefix');
		$link->setHost(null);
		static::assertEquals($this->globalPrefix . '/prefix?a=b', $link . '');
	}

	public function test_makeLinkCSimple(): void
	{
		$c = new AppController4Test();
		$c->linker->useRouter = false;
		$c->request = $this->request;

		$link = $c->linker->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		static::assertEquals($this->globalPrefix . '/?a=b&c=Controller', $link . '');
	}

	public function test_makeLinkCRouter(): void
	{
		$c = new AppController4Test();
		$c->linker->useRouter = true;
		$c->request = $this->request;

		$link = $c->linker->makeURL(['a' => 'b', 'c' => 'Controller']);
		$link->setHost(null);
		static::assertEquals($this->globalPrefix . '/Controller?a=b', $link . '');
	}

}
