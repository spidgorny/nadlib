<?php

class SQLSelect
{

	var $db;

	protected $parts = [];

	function __construct($parts)
	{
		if (is_array($parts)) {
			$this->parts = $parts;
		} else if ($parts) {
			$this->parts[] = $parts;
		} else {
			$this->parts[] = '*';
		}
	}

	function injectDB(DBInterface $db)
	{
		$this->db = $db;
	}

	function __toString()
	{
		return implode(', ', $this->parts);
	}

}
