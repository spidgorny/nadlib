<?php

class UnitTestBase
{

	public function error($message): void
	{
		error_log($message);
	}

	public function assertEquals($must, $is, $message = 'assertEquals'): void
	{
		if ($must != $is) {
			$this->error($message);
			debug($is);
		}
	}

	public function assertCount($must, iterable $array, $message = 'assertCount'): void
	{
		if (count($array) != $must) {
			$this->error($message);
		}
	}

	public function assertTrue($check, $message = 'assertTrue'): void
	{
		if (!$check) {
			$this->error($message);
		}
	}

}
