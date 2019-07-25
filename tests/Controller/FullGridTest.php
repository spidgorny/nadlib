<?php

namespace nadlib\Controller;

use FullGrid;

class FullGridTest extends \PHPUnit\Framework\TestCase
{

	public function setUp()
	{
		$config = Config::getInstance();
		$config->setDB(new \DBPlacebo());
	}

	public function testGetColumnsForm()
	{
		$fg = new FullGrid4Test();
		$fg->getColumnsForm();
	}
}
