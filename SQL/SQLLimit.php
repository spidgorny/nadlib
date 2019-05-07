<?php

class SQLLimit
{

	var $limit;

	var $offset;

	function __construct($limit, $offset = 0)
	{
		$this->limit = $limit;
		$this->offset = $offset;
	}

	function __toString()
	{
		$content = 'LIMIT ' . $this->limit;
		if ($this->offset) {
			$content .= ' OFFSET ' . $this->offset;
		}
		return $content;
	}

}
