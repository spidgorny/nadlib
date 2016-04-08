<?php

class ClassDurationTest extends PHPUnit_Framework_TestCase
{

	/** @var Duration */
	protected $classDuration;

	protected function setUp()
	{
		$this->classDuration = new Duration();

		/** @var $date (uses the date 2001-01-01 10:12:15 in tests) */
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$this->classDuration->setTime($date->getTimestamp());
	}

	public function testFromSeconds()
	{
		$result = Duration::fromSeconds($this->classDuration->getRefactoredTime()+300);
		$this->assertEquals('2001-01-01 10:17:15', $result->toSQL());
	}

	public function testGetTime()
	{
		$this->assertEquals('09:12:15', $this->classDuration->getTime());
	}

	public function testNice()
	{
		$duration	= new Duration(86400*5);
		$this->assertEquals('5 days', $duration->nice());

		$duration	= new Duration(86400);
		$this->assertEquals('1 day', $duration->nice());
	}

	public function testShort()
	{
		$duration	= new Duration(86400*5);
		$this->assertEquals('120h', $duration->short());

		$duration	= new Duration(86400);
		$this->assertEquals('24h', $duration->short());
	}

	public function testFromHuman()
	{
		$result 	= Duration::fromHuman((60*30).'s');
		$this->assertEquals('00:30:00', $result->getTime());

		$result 	= Duration::fromHuman((1*67).'m');
		$this->assertEquals('01:07:00', $result->getTime());

		$result 	= Duration::fromHuman('1h 7m');
		$this->assertEquals('01:07:00', $result->getTime());
	}

}