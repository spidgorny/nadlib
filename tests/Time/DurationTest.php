<?php

class DurationTest extends AppDev\OnlineRequestSystem\Framework\TestCase
{

	public function test_Duration_fromHuman(): void
	{
		$d = Duration::fromHuman('1s');
		$this->assertEquals($d . '', '1 second');
	}

	public function test_Duration_fromHuman2(): void
	{
		$d = Duration::fromHuman('70m 60s');
		$this->assertEquals($d . '', '1 hour, 11 minutes');
	}

}
