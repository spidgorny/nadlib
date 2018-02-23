<?php

class MockIndex {

	function error($message)
	{
		error_log($message);
	}

	function renderException(Exception $e)
	{
		error_log('>> ' . get_class($e));
		error_log($e->getMessage());
	}

}
