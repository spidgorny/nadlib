<?php

namespace nadlib\Controller;

use FullGrid;

class FullGridTest extends \PHPUnit\Framework\TestCase
{

	public function setUp()
	{
		self::markTestSkipped('PG dependent');
		$config = \Config::getInstance();
		$config->setDB(new \DBPlacebo());
	}

	public function testGetColumnsForm()
	{
		$fg = new \FullGrid4Test();
		$fg->postInit();
		$fg->getColumnsForm();
		$this->assertTrue(true);	// not crashed
	}

}
