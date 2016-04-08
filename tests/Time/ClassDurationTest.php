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

	// check with slava
	public function testFromHuman()
	{
		$result 	= Duration::fromHuman((60*30).'s');
		$this->assertEquals('00:30:00', $result->getTime());

		$result 	= Duration::fromHuman((1*67).'m');
		$this->assertEquals('01:07:00', $result->getTime());

		$result 	= Duration::fromHuman('1h 7m');
		$this->assertEquals('01:07:00', $result->getTime());
	}

	public function testLessFalse()
	{
		$objTime = new Time();

		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objTime->setTime($date->getTimestamp());

		$duration	= new Duration(time());
		$this->assertFalse($duration->less($objTime));
	}

	public function testLessTrue()
	{
		$objTime = new Time();

		$date = new DateTime('2022-01-01');
		$date->setTime(10, 12, 15);

		$objTime->setTime($date->getTimestamp());

		$duration	= new Duration(time());
		$this->assertTrue($duration->less($objTime));
	}

	public function testMoreTrue()
	{
		$objTime = new Time();

		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objTime->setTime($date->getTimestamp());

		$duration	= new Duration(time());
		$this->assertTrue($duration->more($objTime));
	}

	public function testMoreFalse()
	{
		$objTime = new Time();

		$date = new DateTime('2022-01-01');
		$date->setTime(10, 12, 15);

		$objTime->setTime($date->getTimestamp());

		$duration	= new Duration(time());
		$this->assertFalse($duration->more($objTime));
	}

	public function testGetMinutes()
	{
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$duration	= new Duration($date->getTimestamp());
		$this->assertEquals('16305672.25', (string)$duration->getMinutes());
	}

	public function testGetHours()
	{
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$duration	= new Duration($date->getTimestamp());
		$this->assertEquals('271761.20416667', (string)$duration->getHours());
	}

	public function testGetDays()
	{
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$duration	= new Duration($date->getTimestamp());
		$this->assertEquals('11323.383506944', (string)$duration->getDays());
	}

	public function testGetRemHours()
	{
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$duration	= new Duration($date->getTimestamp());
		$this->assertEquals('271761', (string)$duration->getRemHours());
	}

	public function testGetRemMinutes()
	{
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$duration	= new Duration($date->getTimestamp());
		$this->assertEquals('12', (string)$duration->getRemMinutes());
	}

	public function testRemSeconds()
	{
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$duration	= new Duration($date->getTimestamp());
		$this->assertEquals('15', (string)$duration->getRemSeconds());
	}

}