<?php

class SQLHaving extends SQLWhere
{

	protected $parts = [];

	function __construct($order = [])
	{
		if (is_array($order)) {
			$this->parts = $order;
		} else if ($order) {
			$this->parts[] = $order;
		}
	}

	function __toString()
	{
		if ($this->parts) {
			return 'ORDER BY ' . implode(' ', $this->parts);
		} else {
			return '';
		}
	}

}
