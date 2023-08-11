<?php

class SQLOrder
{

	var $db;

	protected $parts = [];

	function __construct($order = [])
	{
		if (is_array($order)) {
			$this->parts = $order;
		} elseif ($order) {
			$this->parts[] = str_replace('ORDER BY', '', $order);
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
