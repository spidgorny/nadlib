<?php

/**
 * Created by PhpStorm.
 * User: debagsuy
 * Date: 06.04.2016
 * Time: 15:25
 */
class ClassTimeTest extends PHPUnit_Framework_TestCase
{

	/** @var Time */
	protected $classTime;

	protected function setUp()
	{
		$this->classTime = new Time();

		/** @var $date (uses the date 2001-01-01 10:12:15 in tests) */
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$this->classTime->setTime($date->getTimestamp());
	}

	public function testToSqlSuccess()
	{
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->toSQL());
	}

	public function testToSqlFails()
	{
		$this->assertNotEquals('2001-01-01 10:11:11', $this->classTime->toSQL());
	}

	public function testGetGMTTimestampSuccess()
	{
		$this->assertEquals('978343935', $this->classTime->getGMTTimestamp());
	}

	public function testGetGMTTimestampFails()
	{
		$this->assertNotEquals('97834393511', $this->classTime->getGMTTimestamp());
	}

	public function testMath()
	{
		$this->assertInstanceOf('Time', $this->classTime->math("+1 day"));
	}

	public function testEarlierTrue()
	{
		$objTime = new Time();
		$date = new DateTime('2001-02-01');
		$date->setTime(10, 10, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertTrue($this->classTime->earlier($objTime));
	}

	public function testEarlierFalse()
	{
		$objTime = new Time();
		$date = new DateTime('2000-01-01');
		$date->setTime(10, 10, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertFalse($this->classTime->earlier($objTime));
	}

	public function testEarlierOrEqualTrue()
	{
		$objTime = new Time();
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertTrue($this->classTime->earlierOrEqual($objTime));
	}

	public function testEarlierOrEqualFalse()
	{
		$objTime = new Time();
		$date = new DateTime('2000-01-01');
		$date->setTime(10, 10, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertFalse($this->classTime->earlier($objTime));
	}

	public function testLaterOrEqualTrue()
	{
		$objTime = new Time();
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 12, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertTrue($this->classTime->laterOrEqual($objTime));
	}

	public function testLaterOrEqualFalse()
	{
		$objTime = new Time();
		$date = new DateTime('2001-01-02');
		$date->setTime(9, 10, 10);

		$objTime->setTime($date->getTimestamp());

		$this->assertFalse($this->classTime->laterOrEqual($objTime));
	}

	public function testLaterTrue()
	{
		$objTime = new Time();
		$date = new DateTime('2001-01-01');
		$date->setTime(10, 10, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertTrue($this->classTime->later($objTime));
	}

	public function testLaterFalse()
	{
		$objTime = new Time();
		$date = new DateTime('2001-02-01');
		$date->setTime(11, 10, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertFalse($this->classTime->later($objTime));
	}

	public function testGetISO()
	{
		/* Ymd\THis\Z */
		$this->assertEquals('20010101T091215Z', $this->classTime->getISO());
		$this->assertNotEquals('20020101T091215Z', $this->classTime->getISO());
	}

	public function testGetISODate()
	{
		/* Y-m-d */
		$this->assertEquals('2001-01-01', $this->classTime->getISODate());
		$this->assertNotEquals('2002-02-01', $this->classTime->getISODate());
	}

	public function testGetISODateTime()
	{
		/* Y-m-d H:i:s */
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->getISODateTime());
		$this->assertNotEquals('2002-02-01 10:12:15', $this->classTime->getISODateTime());
	}

	public function testGetHumanDate()
	{
		/* d.m.Y */
		$this->assertEquals('01.01.2001', $this->classTime->getHumanDate());
		$this->assertNotEquals('01.01.2002', $this->classTime->getHumanDate());
	}

	public function testGetMySQL()
	{
		/* Y-m-d H:i:s */
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->getMySQL());
		$this->assertNotEquals('2001-01-01 10:11:11', $this->classTime->getMySQL());
	}

	public function testGetMySQLUTC()
	{
		/* Y-m-d H:i:s */
		$this->assertEquals('2001-01-01 09:12:15', $this->classTime->getMySQLUTC());
		$this->assertNotEquals('2001-01-01 10:11:11', $this->classTime->getMySQLUTC());
	}

	public function testGetHumanTime()
	{
		/* H:i */
		$this->assertEquals('10:12', $this->classTime->getHumanTime());
		$this->assertNotEquals('11:11', $this->classTime->getHumanTime());
	}

	public function testGetHumanTimeGMT()
	{
		/* H:i */
		$this->assertEquals('09:12', $this->classTime->getHumanTimeGMT());
		$this->assertNotEquals('11:11', $this->classTime->getHumanTimeGMT());
	}

	public function testGetDateTime()
	{
		/* Y-m-d H:i:s */
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->getDateTime());
		$this->assertNotEquals('2001-01-01 11:12:15', $this->classTime->getDateTime());
	}

	public function testIn()
	{

		$time = new Time();
		$date = new DateTime('2012-02-01');
		$date->setTime(11, 10, 15);

		$time->setTime($date->getTimestamp());

		$this->assertEquals('4 years ago', $time->in());
	}

	public function testRender()
	{
		$this->assertInstanceOf('HTMLTag', $this->classTime->renderCaps());
	}

	public function testFormat()
	{
		$this->assertEquals('10:12', $this->classTime->format('H:i'));
		$this->assertNotEquals('2001-01-01 11:12:15', $this->classTime->format('H:i'));
	}

	public function testGmFormat()
	{
		$this->assertEquals('09:12', $this->classTime->gmFormat('H:i'));
		$this->assertNotEquals('2001-01-01 11:12:15', $this->classTime->gmFormat('H:i'));
	}

	public function testGetSystem()
	{
		/** Y-m-d H:i:s */
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->getSystem());
		$this->assertNotEquals('2001-01-01 11:12:15', $this->classTime->getSystem());
	}

	public function testAdd()
	{
		$objTime = new Time();
		$date = new DateTime('2002-02-01');
		$date->setTime(11, 10, 15);

		$objTime->setTime($date->getTimestamp());

		$this->assertInstanceOf('Time', $this->classTime->add($objTime));
	}

	public function testAddDur()
	{
		$objTime = new Duration(60*5);	// 5 min
		$result = $this->classTime->addDur($objTime);
		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('2001-01-01 10:17:15', $result->getMySQL());
	}

	public function testSubstract()
	{
		$objTime = new Duration(60*5);	// 5 min

		$result = $this->classTime->substract($objTime);
		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('2001-01-01 10:07:15', $result->getMySQL());

	}

	public function testPlus()
	{
		$objTime	= new Time(60*5);	// 5 min
		$result		= $this->classTime->plus($objTime);

		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('2001-01-01 10:17:15', $result->getMySQL());
	}

	public function testPlusDur()
	{
		$objTime	= new Duration(60*5);	// 5 min
		$result		= $this->classTime->plusDur($objTime);
		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('2001-01-01 10:17:15', $result->getMySQL());
	}

	public function testMinus()
	{
		$objTime	= new Time(60*5);	// 5 min
		$result		= $this->classTime->minus($objTime);
		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('2001-01-01 10:07:15', $result->getMySQL());
	}

	public function testMinus2()
	{
		$objTime	= new Time(60*5);	// 5 min
		$result		= $this->classTime->minus2($objTime);
		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('2001-01-01 10:07:15', $result->getMySQL());
	}

	public function testGetDiff()
	{
		$objTime	= new Time(60*5);	// 5 min
		$result		= $this->classTime->getDiff($objTime);
		$this->assertEquals(978340035, $result);
	}

	public function testModify()
	{
		$this->assertInstanceOf('Time', $this->classTime->modify('Y-m-d H:i:s'));
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->toSQL());
	}

	public function testGetModify()
	{
		$this->assertInstanceOf('Time', $this->classTime->getModify('Y-m-d H:i:s'));
		$this->assertEquals('2001-01-01 10:12:15', $this->classTime->toSQL());
	}

	public function testGetAdjustedForTZ()
	{
		$this->assertEquals('978343935', $this->classTime->getAdjustedForTZ());
	}

	public function testGetAdjustedForUTC()
	{
		$this->assertEquals('978336735', $this->classTime->getAdjustedForUTC());
	}

	public function testGetDurationDayAgo()
	{

		// day test
		$time = new Time();
		$time->setTime(time() - 60*60*24);
		$this->assertEquals('1 day ago', $time->getDuration());

		// month test
		$time = new Time();
		$time->setTime(time() - 60*60*24*32);
		$result = $time->getDuration();
		$this->assertContains('month', $result);

		// year test
		$time = new Time();
		$time->setTime(time() - 60*60*24*400);
		$result = $time->getDuration();
		$this->assertContains('month', $result);
		$this->assertContains('year', $result);

		// 978343935 is the timestamp value for 01/01/2001 10:12:15am
		$time = new Time();
		$time->setTime(978343935 - 60*60*24);

		$result = $time->getDuration();
		$this->assertContains('decade', $result);
		$this->assertContains('year', $result);
		$this->assertContains('month', $result);
		$this->assertContains('week', $result);
	}


	public function testAdjust()
	{
		$this->assertInstanceOf('Time', $this->classTime->adjust('2016-01-01 10:12:15'));
		$this->assertEquals('1451639535', $this->classTime->adjust('2016-01-01 10:12:15')->getTimestamp());
	}

	public function testCombine()
	{
		$result = Time::combine('2016-01-01', '10:12:15');
		$this->assertInstanceOf('Time', $result);
		$this->assertEquals('1451639535', $result->getTimestamp());
	}

	public function testGetTimeIn()
	{
		$this->assertEquals('10:12', $this->classTime->getTimeIn('Europe/Berlin'));
	}

	public function testMakeInstance()
	{
		$result = Time::makeInstance('2016-01-01');
		$this->assertInstanceOf('Time', $result);
	}

	public function testGetTwo()
	{
		$this->assertEquals('mo', strtolower($this->classTime->getTwo()));
	}

	public function testGetDurationObject()
	{
		$this->assertInstanceOf('Duration', $this->classTime->getDurationObject());
	}

	public function testOlder()
	{
		$this->assertFalse(false, $this->classTime->older(time() + 60*5));
		$this->assertTrue(true, $this->classTime->older(60*5));
	}

	public function testGetDateObject()
	{
		$this->assertInstanceOf('Date', $this->classTime->getDateObject());
	}

	public function testGetHTMLDate()
	{
		$this->assertContains('01.01.2001', $this->classTime->getHTMLDate()->__toString());
		$this->assertContains('2001-01-01', $this->classTime->getHTMLDate()->__toString());
	}

	public function testGetHTMLTime()
	{
		$this->assertContains('10:12', $this->classTime->getHTMLTime()->__toString());
	}

	public function testGetHTMLTimeGMT()
	{
		$this->assertContains('10:12', $this->classTime->getHTMLTimeGMT()->__toString());
	}

	public function testGetSince()
	{
		$expectedValue = $this->classTime->getTimestamp() - time();
		$this->assertEquals($expectedValue, $this->classTime->getSince()->getTimestamp());
	}

	public function testGetAge()
	{
		$expectedValue = time() - $this->classTime->getTimestamp();
		$this->assertEquals($expectedValue, $this->classTime->getAge()->getTimestamp());
	}

	public function testIsToday()
	{
		$this->assertFalse($this->classTime->isToday());

		$time = new Time();
		$time->setTime(time());
		$this->assertTrue($time->isToday());
	}

	public function testIsFuture()
	{
		$time = new Time();
		$time->setTime(time() + 604800);
		$this->assertTrue($time->isFuture());

		$time->setTime(time() - 604800);
		$this->assertFalse($time->isFuture());
	}

	public function testIsPast()
	{
		$time = new Time();
		$time->setTime(time() + 604800);
		$this->assertFalse($time->isPast());

		$time->setTime(time() - 604800);
		$this->assertTrue($time->isPast());
	}

	public function testAddTime()
	{
		$duration = new Duration();
		$date = new DateTime();
		$date->setTime(11, 11, 11);
		$duration->setTime(time());
		$this->assertInstanceOf('Time', $this->classTime->addTime($duration));
	}

}