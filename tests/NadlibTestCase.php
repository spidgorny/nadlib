<?php

class NadlibTestCase extends PHPUnit\Framework\TestCase
{

	public $canPrint = false;

	public function log(...$something)
	{
		if (!$this->canPrint) {
			return;
		}
		echo implode(TAB, $something), PHP_EOL;
	}

}
