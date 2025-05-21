<?php

namespace Time;

use Date;
use Duration;
use Time;

class TimeTest extends \PHPUnit\Framework\TestCase
{

	protected function setUp(): void
	{
		self::markTestSkipped('PG dependent');
	}

	public function test_Time_add_Duration(): void
	{
		$t = new Date('2011-08-01');
		$d = new Duration('1d');
		$t->addDur($d);
		static::assertEquals('2011-08-02', $t->getSystem(), '2011-08-01 + 1d');
	}

	public function test_Time_constructor(): void
	{
		$t = new Time();
		//debug($t);
		static::assertEquals($t->getTimestamp(), time());
	}

	public function test_Time_plus(): void
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->plus(new Time('1970-01-01 00:00:01'));
		static::assertEquals($t->getSystem(), $source);
	}

	public function test_Time_add(): void
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->add(new Time('1970-01-01 00:00:01 GMT'));
		static::assertEquals($t->getSystem(), '1970-01-01 12:34:57');
	}

	public function test_Time_plus_Duration(): void
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->add(new Duration('00:00:01'));
		static::assertEquals($t->getSystem(), '1970-01-01 12:34:57');
	}

	public function test_Time_earlier(): void
	{
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		static::assertTrue(!$t->earlier(new Time('1970-01-01 12:34:55')));
		static::assertTrue($t->earlier(new Time('1970-01-01 12:34:57')));
		static::assertTrue($t->earlierOrEqual(new Time($source)));
	}

	public function test_Time_addError(): void
	{
		$source = '1970-01-01 00:45:00';
		$sourceTS = strtotime($source);
		static::assertEquals(date('Y-m-d H:i:s', $sourceTS) . '~' . $sourceTS, $source . '~-900');
		$target = strtotime('+ 0 years 0 months 0 days 00 hours 15 minutes 00 seconds', $sourceTS);
		static::assertEquals(date('Y-m-d H:i:s', $target) . '~' . $target, '1970-01-01 01:00:00~0');
	}

	public function test_Time_addErrorTime(): void
	{
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$target = $sourceT->plus(new Time('1970-01-01 00:14:00 GMT'), true);
		static::assertEquals($target->getSystem(), '1970-01-01 00:59:00');
		$target = $sourceT->plus(new Time('1970-01-01 00:15:00 GMT'));
		static::assertEquals($target->getSystem(), '1970-01-01 01:00:00');
	}

	public function test_Time_addRelative(): void
	{
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$target = $sourceT->plus(new Time('00:10:00 GMT', 0));
		static::assertEquals($target->getSystem(), '1970-01-01 00:55:00');
		$target = $sourceT->plus(new Time(10 * 60));
		static::assertEquals($target->getSystem(), '1970-01-01 00:55:00');
		$target = new Time('00:55:00', 0);
		static::assertEquals($target->getSystem(), '1970-01-01 00:55:00');
	}

	public function test_Time_minus(): void
	{
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$target = $sourceT->minus(new Time('00:10:00 GMT', 0));
		static::assertEquals($target->getSystem(), '1970-01-01 00:35:00');
		$target = $sourceT->minus2(new Time('00:10:00 GMT', 0));
		static::assertEquals($target->getSystem(), '1970-01-01 00:35:00');
	}

	public function test_addTime(): void
	{
		$t = new Time('2016-03-08 15:05');
		$sTime = '01:12:34';
		$t->addTime($sTime);
		static::assertEquals('2016-03-08 16:17:34', $t->getISODateTime());
	}

	public function test_getAge1(): void
	{
		$t = new Time('2016-03-08 15:05');
		$age = $t->getAge();
		$olderThan30 = $age->biggerThan(Duration::fromHuman('30d'));
//		debug($t, $age, $olderThan30);
		static::assertTrue($olderThan30);
	}

	public function test_getAge2(): void
	{
		$t = new Time('2016-03-08 15:05');
		$age = $t->getAge();
		$olderThan30 = $age->biggerThan(Duration::fromHuman('10y'));
//		debug($t, $age, $olderThan30);
		static::assertFalse($olderThan30);
	}

}
