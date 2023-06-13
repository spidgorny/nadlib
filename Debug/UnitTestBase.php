<?php

class UnitTestBase {

	function error($message)
	{
		error_log($message);
	}

	function assertEquals($must, $is, $message = 'assertEquals')
	{
		if ($must != $is) {
			$this->error($message);
			debug($is);
		}
	}

	function assertCount($must, iterable $array, $message = 'assertCount')
	{
		if (count($array) != $must) {
			$this->error($message);
		}
	}

	function assertTrue($check, $message = 'assertTrue')
	{
		if (!$check) {
			$this->error($message);
		}
	}

}
