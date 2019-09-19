<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2016-01-24
 * Time: 23:17
 */
class URLTest extends PHPUnit_Framework_TestCase
{

	function test_resolve_append()
	{
		$url = new URL('http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html');
		$abs = $url->resolve('image.png');
//		debug($url->log);
		$this->assertEquals('http://www.thueringer-wald.com/urlaub-wandern-winter/image.png', $abs);
	}

	function test_resolve_parent()
	{
		$url = new URL('http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html');
		$abs = $url->resolve('../image.png');
//		debug($url->log);
		$this->assertEquals('http://www.thueringer-wald.com/image.png', $abs);
	}

	function test_resolve_root()
	{
		$url = new URL('http://www.thueringer-wald.com/urlaub-wandern-winter/trusetaler-wasserfall-104618.html');
		$abs = $url->resolve('/image.png');
//		debug($url->log);
		$this->assertEquals('http://www.thueringer-wald.com/image.png', $abs);
	}

}
