<?php

namespace nadlib\Controller;

use Config;
use DBPlacebo;
use FullGrid4Test;
use LoginException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class FullGridTest extends TestCase
{

	protected function setUp(): void
	{
		parent::setUp();
		self::markTestSkipped('PG dependent');
		$config = Config::getInstance();
		$config->setDB(new DBPlacebo());
	}

	/**
	 * @throws ReflectionException
	 * @throws LoginException
	 */
	public function testGetColumnsForm(): void
	{
//		$fg = new AppController4Test();
//		$fg->postInit();
//		$fg->getColumnsForm();
//		static::assertTrue(true);  // not crashed
	}

}
