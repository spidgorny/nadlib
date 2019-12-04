<?php

class MockIndexDCI
{

	function error($message)
	{
		error_log(__METHOD__ . ': ' . $message);
	}

	function renderException(Exception $e)
	{
		error_log('>> ' . get_class($e));
		error_log($e->getMessage());
	}

}
