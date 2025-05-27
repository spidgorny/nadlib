<?php

namespace Time;

use Date;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{

	public function test_Date(): void
	{
		$d = new Date(1306879200);
		static::assertEquals('01.06.2011', $d);
	}

	public function test_Date_preserveTimestamp(): void
	{
		$d = new Date(1306879200);
		static::assertEquals(1306879200, $d->getTimestamp());
	}

	/* // Asserting!
	function test_Date_preserveTimestamp_string() {
		try {
			$d = new Date('1306879200');
		} catch (Exception $e) {

		}
		return $this->assertEqual($d->getTimestamp(), 1306879200);
	}*/

	public function test_NewDateNewDate(): void
	{
		$d = new Date('2011-11-11');
		$d2 = new Date($d);
		static::assertEquals($d, $d2);
	}

}
