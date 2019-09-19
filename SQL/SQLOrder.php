<?php

class SQLOrder
{

	public $db;

	protected $parts = array();

	function __construct($order = array())
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
