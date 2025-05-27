<?php

namespace nadlib;


use PHPUnit\Framework\TestCase;

class NadlibTestCase extends TestCase
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
		static::assertEquals($must, $is);
	}

	public function normalize($s): string
	{
		return implode(PHP_EOL, trimExplode("\n", $s));
	}

}
