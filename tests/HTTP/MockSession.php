<?php

/**
 * Class MockSession
 * When running in PHPUnit we get
 * session_start(): Cannot send session cookie - headers already sent
 * Therefore we hack session with files.
 */
class MockSession implements SessionInterface
{

	var $file;

	var $data = [];

	function __construct($file)
	{
		$this->file = $file;
		if (is_file($file)) {
			$this->data = unserialize(
				file_get_contents($this->file)
			);
		}
	}

	function __destruct()
	{
		file_put_contents($this->file, serialize($this->data));
	}

	function save($key, $val)
	{
		$this->data[$key] = $val;
	}

	function get($key)
	{
		return ifsetor($this->data[$key]);
	}

	function has($key)
	{
		return !!ifsetor($this->data[$key]);
	}

	function getAll()
	{
		return $this->data;
	}

	function delete($key)
	{
		unset($this->data[$key]);
	}

}
