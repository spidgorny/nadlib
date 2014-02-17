<?php

class TimeTest extends PHPUnit_Framework_TestCase {

	function test_Time_add_Duration() {
		$t = new Date('2011-08-01');
		$d = new Duration('1d');
		$t->addDur($d);
		$this->assertEquals($t->getSystem(), '2011-08-02', '2011-08-01 + 1d');
	}

	function test_Time_constructor() {
		$t = new Time();
		//debug($t);
		$this->assertEquals($t->getTimestamp(), time());
	}

	function test_Time_plus() {
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->plus(new Time('1970-01-01 00:00:01'));
		$this->assertEquals($t->getSystem(), $source);
	}

	function test_Time_add() {
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->add(new Time('1970-01-01 00:00:01 GMT'));
		$this->assertEquals($t->getSystem(), '1970-01-01 12:34:57');
	}

	function test_Time_plus_Duration() {
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$t->add(new Duration('00:00:01'));
		$this->assertEquals($t->getSystem(), '1970-01-01 12:34:57');
	}

	function test_Time_earlier() {
		$source = '1970-01-01 12:34:56';
		$t = new Time($source);
		$this->assertTrue(!$t->earlier(new Time('1970-01-01 12:34:55')));
		$this->assertTrue($t->earlier(new Time('1970-01-01 12:34:57')));
		$this->assertTrue($t->earlierOrEqual(new Time($source)));
	}

	function test_Time_addError() {
		$source = '1970-01-01 00:45:00';
		$sourceTS = strtotime($source);
		$this->assertEquals(date('Y-m-d H:i:s', $sourceTS).'~'.$sourceTS, $source.'~-900');
		$destin = strtotime('+ 0 years 0 months 0 days 00 hours 15 minutes 00 seconds', $sourceTS);
		$this->assertEquals(date('Y-m-d H:i:s', $destin).'~'.$destin, '1970-01-01 01:00:00~0');
	}

	function test_Time_addErrorTime() {
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$destin = $sourceT->plus(new Time('1970-01-01 00:14:00 GMT'), true);
		$this->assertEquals($destin->getSystem(), '1970-01-01 00:59:00');
		$destin = $sourceT->plus(new Time('1970-01-01 00:15:00 GMT'));
		$this->assertEquals($destin->getSystem(), '1970-01-01 01:00:00');
	}

	function test_Time_addRelative() {
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$destin = $sourceT->plus(new Time('00:10:00 GMT', 0));
		$this->assertEquals($destin->getSystem(), '1970-01-01 00:55:00');
		$destin = $sourceT->plus(new Time(10*60));
		$this->assertEquals($destin->getSystem(), '1970-01-01 00:55:00');
		$destin = new Time('00:55:00', 0);
		$this->assertEquals($destin->getSystem(), '1970-01-01 00:55:00');
	}

	function test_Time_minus() {
		$source = '1970-01-01 00:45:00';
		$sourceT = new Time($source);
		$destin = $sourceT->minus(new Time('00:10:00 GMT', 0));
		$this->assertEquals($destin->getSystem(), '1970-01-01 00:35:00');
		$destin = $sourceT->minus2(new Time('00:10:00 GMT', 0));
		$this->assertEquals($destin->getSystem(), '1970-01-01 00:35:00');
	}

}
