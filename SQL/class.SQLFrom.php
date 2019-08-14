<?php

class SQLFrom
{

	protected $parts = array();

	function __construct($from)
	{
		$this->parts[] = $from;
	}

	function __toString()
	{
		return implode(', ', $this->parts);
	}

}
