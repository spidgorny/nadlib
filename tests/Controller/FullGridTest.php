<?php

namespace nadlib\Controller;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use Config;
use DBPlacebo;
use FullGrid4Test;

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
		$this->assertTrue(true);  // not crashed
	}

}
