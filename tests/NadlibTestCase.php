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


	public function assertEqualsIgnoreSpaces($must, $is): void
	{
		$must = $this->normalize($must);
		$is = $this->normalize($is);
		static::assertEquals($must, $is);
	}

	public function normalize($s): string
	{
		// Replace all kinds of line breaks with spaces
		$s = preg_replace('/\r\n|\r|\n/', ' ', $s);
		// Replace multiple spaces with one space
		$s = preg_replace('/\s+/', ' ', $s);
		return trim($s);
	}

}
