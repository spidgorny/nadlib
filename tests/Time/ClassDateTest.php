<?php

/**
 * Created by PhpStorm.
 * User: debagsuy
 * Date: 06.04.2016
 * Time: 15:25
 */
class ClassDateTest extends PHPUnit_Framework_TestCase
{

	/** @var Date */
	protected $classDate;

	protected function setUp()
	{
		$this->classDate = new Date();

		/** @var $date (uses the date 2001-01-01 10:12:15 in tests) */
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$this->classDate->setTime($date->getTimestamp());
	}

	public function testGetMySQL()
	{
		/** Y-m-d H:i:s */
		$this->assertEquals('2001-01-01 10:12:15', $this->classDate->toSQL());
	}

	public function testGetMySQLUTC()
	{
		/** Y-m-d */
		$this->assertEquals('2001-01-01', $this->classDate->getMySQLUTC());
	}

	public function testGetISO()
	{
		/** Y-m-d */
		$this->assertEquals('2001-01-01', $this->classDate->getISO());
	}

	public function testGetGMT()
	{
		/** Y-m-d */
		$this->assertEquals('2001-01-01', $this->classDate->getGMT());
	}

	public function testFromEurope()
	{
		$result = Date::fromEurope('01.01.2001');
		$this->assertInstanceOf('Date', $result);
		$this->assertEquals('2001-01-01 00:00', $result->getHuman());
	}

	public function testMathCurrent()
	{
		$now	= strtotime('now');

		$date	= new Date();
		$date->setTime($now);

		$result	= $date->math('now');

		$this->assertInstanceOf('Date', $result);
		$this->assertContains(date('Y-m-d', $now), $result->getHuman());
	}

	public function testMathFuture()
	{
		$testTime	= strtotime('+5 minutes');
		$date		= new Date();
		$date->setTime($testTime);

		$result	= $date->math('+5 minutes');

		$this->assertInstanceOf('Date', $result);
		$this->assertContains(date('Y-m-d', $testTime), $result->getHuman());
	}

	public function testHtml()
	{
		$this->assertContains('01.01.2001', $this->classDate->html()->__toString());
	}

	public function testDays()
	{
		$this->markTestIncomplete(
			'Ask slava'
		);

		$testTime	= time() - (60 * 60 * 48);
		$date		= new Date();
		$date->setTime($testTime);

		$this->assertEquals('01.01.2001', $date->days());
	}

	public function testGetSystem()
	{
		$this->assertEquals('2001-01-01', $this->classDate->getSystem());
	}

	public function testPlusDur()
	{
		$date 		= new DateTime('2001-01-03');
		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$duration	= new Duration(86400); // one day
		$result		= $objDate->plusDur($duration);
		
		$this->assertEquals('2001-01-04', $result->getSystem());

		$duration	= new Duration(86400 * 2); // two days
		$result		= $objDate->plusDur($duration);

		$this->assertEquals('2001-01-05', $result->getSystem());
	}

	public function testMinusDur()
	{
		$date 		= new DateTime('2001-01-03');
		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$duration	= new Duration(86400); // one day
		$result		= $objDate->minusDur($duration);

		$this->assertEquals('2001-01-02', $result->getSystem());

		$duration	= new Duration(86400 * 2); // two days
		$result		= $objDate->minusDur($duration);

		$this->assertEquals('2001-01-01', $result->getSystem());
	}

	public function testFromHuman()
	{
		$result = Date::fromHuman('10 September 2001');
		$this->assertEquals('2001-09-10', $result->getSystem());
	}

	public function testIsWeekend()
	{
		$result = Date::fromHuman('09 September 2001');
		$this->assertTrue($result->isWeekend());

		$result = Date::fromHuman('10 September 2001');
		$this->assertfalse($result->isWeekend());
	}

	public function testGetHumanMerged()
	{
		$this->assertEquals('20010101', $this->classDate->getHumanMerged());
	}

	public function testFromMerged()
	{
		$date 		= new DateTime('2001-01-03');
		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$objDate->fromMerged('20010101');
		$this->assertEquals('20010101', $objDate->getHumanMerged());
	}

	public function testGetDow()
	{
		$date 		= new DateTime('2001-01-01');
		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$this->assertEquals('Mon', $objDate->getDOW());
	}

	public function testGetApproximate()
	{
		$date 		= new DateTime('2001-01-01');
		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$this->assertContains('2001-01-01', $objDate->getApproximate()->__toString());
	}

	public function testAddTime()
	{
		$date 		= new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$result 	= $objDate->addTime(15);

		$this->assertEquals('2001-01-01 10:12:30', $result->getDateTime());
	}

	public function testIsFuture()
	{
		$date 		= new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$this->assertFalse($objDate->isFuture());

		$date 		= new DateTime('2030-01-01');
		$date->setTime(10, 12, 15);
		$objDate->setTime($date->getTimestamp());

		$this->assertTrue($objDate->isFuture());
	}

	public function testIsPast()
	{
		$date 		= new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objDate	= new Date();
		$objDate->setTime($date->getTimestamp());

		$this->assertTrue($objDate->isPast());

		$date 		= new DateTime('2030-01-01');
		$date->setTime(10, 12, 15);
		$objDate->setTime($date->getTimestamp());

		$this->assertFalse($objDate->isPast());
	}

}