<?php

namespace Controller;

use AppBundle\Controller\Request\SelfServiceController;
use PHPUnit\Framework\TestCase;
use TestConfig;

class SelfServiceControllerTest extends TestCase
{

	public function setUp()
	{
		chdir(dirname(__FILE__).'/../../../../../');
	}

	public function testGetRssScripts()
	{
		$tc = TestConfig::getInstance();
		$ssc = new SelfServiceController();
		$tag = $ssc->getRssScripts();
		self::assertStringStartsWith('<script src', $tag);
	}

	public function testGetRssStyles()
	{
		$tc = TestConfig::getInstance();
		$ssc = new SelfServiceController();
		$tag = $ssc->getRssStyles();
		self::assertStringStartsWith('<link rel', $tag);
	}
}
