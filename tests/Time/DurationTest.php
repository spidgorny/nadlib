<?php

namespace Time;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use Duration;

class DurationTest extends TestCase
{

	public function test_Duration_fromHuman(): void
	{
		$d = Duration::fromHuman('1s');
		static::assertEquals('1 second', $d . '');
	}

	public function test_Duration_fromHuman2(): void
	{
		$d = Duration::fromHuman('70m 60s');
		static::assertEquals('1 hour, 11 minutes', $d . '');
	}

}
