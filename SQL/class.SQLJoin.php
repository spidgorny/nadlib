<?php

class SQLJoin
{

	protected $parts = array();

	function __construct($join = NULL)
	{
		if (is_array($join)) {
			$this->parts = $join;
		} else {
			$this->parts[] = $join;
		}
	}

	function add($join)
	{
		if (!in_array($join, $this->parts)) {
			$this->parts[] = $join;
		}
	}

	function __toString()
	{
		if ($this->parts) {
			return implode("\n", $this->parts);
		}
	}

}
