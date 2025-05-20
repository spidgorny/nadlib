<?php

class NadlibTestCase extends AppDev\OnlineRequestSystem\Framework\TestCase
{

	public $canPrint = false;

	public function log(...$something): void
	{
		if (!$this->canPrint) {
			return;
		}

		echo implode(TAB, $something), PHP_EOL;
	}


	public function assertEqualsIngnoreSpaces($must, $is): void
	{
		$must = $this->normalize($must);
		$is = $this->normalize($is);
		$this->assertEquals($must, $is);
	}

	public function normalize($s): string
	{
		return implode(PHP_EOL, trimExplode("\n", $s));
	}

}
