<?php

namespace HTTP;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use Request;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 19.12.13
 * Time: 15:03
 */
class RequestTest extends TestCase
{

	/**
	 * @var Request $r
	 */
	protected $r;

	protected function setUp(): void
	{
		$this->r = Request::getInstance();
	}

	public function test_set(): void
	{
		$this->r->set('a', 'b');
		static::assertEquals('b', $this->r->getTrim('a'));
	}

	public function test_unset(): void
	{
		//debug($this->r);
		$this->r->set('a', 'b');
		$this->r->un_set('a');
		static::assertEmpty($this->r->getTrim('a'));
	}

	public function test_getTrim(): void
	{
		$this->r->set('a', ' some words' . "\n\t");
		static::assertEquals('some words', $this->r->getTrim('a'));
	}

	/**
	 * @expectedException Exception
	 */
	public function test_getTrimRequired(): void
	{
		$this->r->set('a', '  ');
		$this->r->getTrimRequired('a');
	}

	/**
	 * @expectedException Exception
	 */
	public function test_getOneOf(): void
	{
		$this->r->set('a', 'b');
		$this->r->getOneOf('a', ['c']);
	}

	public function test_getInt(): void
	{
		$this->r->set('i', '10');
		static::assertEquals(10, $this->r->getInt('i'));
	}

	public function test_getInt0(): void
	{
		$this->r->set('i', '10');
		static::assertEquals(0, $this->r->getInt('new'));
	}

	public function test_getIntOrNULL(): void
	{
		static::assertNull($this->r->getIntOrNULL('new'));
	}

	public function test_getIntIn(): void
	{
		$this->r->set('i', 10);
		static::assertEquals(10, $this->r->getIntIn('i', [
			9 => '',
			10 => '',
			11 => '',
		]));
	}

	public function test_getIntIn0(): void
	{
		$this->r->set('i', 10);
		static::assertNull($this->r->getIntIn('i', [
			9 => '',
			11 => '',
		]));
	}

	public function test_getLocation(): void
	{
		$_SERVER['DOCUMENT_ROOT'] = 'Z:/web/dev-jobz/htdocs/';
		$_SERVER['HTTP_HOST'] = 'dev-jobz.local';
		$location = Request::getLocation();
//		debug($location . '');
		static::assertEquals('http://' . gethostname() . '/', $location);
	}

	public function test_dir_of_file(): void
	{
		$set = [
			'a' => '.',
			'/a' => DIRECTORY_SEPARATOR,  // only case where Windows matter
			'/a/b' => '/a',
			'/a/b/c' => '/a/b',
			'/a/b/c/' => '/a/b/c',
		];
		foreach ($set as $check => $must) {
			static::assertEquals($must, Request::dir_of_file($check));
		}
	}

	public function test_getDocumentRootByIsDir(): void
	{
		$result = Request::getDocumentRootByIsDir();
		static::assertEquals('/', $result);
	}

	public function test_getOnlyHost(): void
	{
		$host = Request::getOnlyHost();
		static::assertEquals(gethostname(), $host);
	}

	public function test_isAjax(): void
	{
		$r = Request::getInstance();
		$r->set('ajax', false);
		static::assertFalse($r->isAjax());
		$r->set('ajax', true);
		static::assertTrue($r->isAjax());
		$r->set('ajax', false);
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		static::assertTrue($r->isAjax());
	}

}
