<?php

class MockIndexDCI
{

	public function error(string $message): void
	{
		error_log(__METHOD__ . ': ' . $message);
	}

	public function renderException(Exception $e): void
	{
		error_log('>> ' . get_class($e));
		error_log($e->getMessage());
	}

}
