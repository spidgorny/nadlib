<?php

namespace nadlib\Controller;

use Config;
use DBPlacebo;
use FullGrid4Test;
use PHPUnit\Framework\TestCase;

class FullGridTest extends TestCase
{

	protected function setUp(): void
	{
		self::markTestSkipped('PG dependent');
		$config = Config::getInstance();
		$config->setDB(new DBPlacebo());
	}

	public function testGetColumnsForm(): void
	{
		$fg = new FullGrid4Test();
		$fg->postInit();
		$fg->getColumnsForm();
		static::assertTrue(true);  // not crashed
	}

}
