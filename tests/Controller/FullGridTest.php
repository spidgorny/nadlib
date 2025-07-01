<?php

namespace nadlib\Controller;

use FullGrid4Test;
use PHPUnit\Framework\TestCase;

class FullGridTest extends TestCase
{

	protected function setUp(): void
	{
		parent::setUp();
		self::markTestSkipped('PG dependent');
//		$config = Config::getInstance();
//		$config->setDB(new DBPlacebo());
	}

	/**
	 */
	public function testGetColumnsForm(): void
	{
//		$fg = new AppController4Test();
//		$fg->postInit();
//		$fg->getColumnsForm();
//		static::assertTrue(true);  // not crashed
	}

}
