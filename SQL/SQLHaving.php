<?php

class SQLHaving extends SQLWhere
{

	protected $parts = [];

	public function __construct($order = [])
	{
		if (is_array($order)) {
			$this->parts = $order;
		} elseif ($order) {
			$this->parts[] = $order;
		}
	}

	public function __toString(): string
	{
		if ($this->parts) {
			return 'ORDER BY ' . implode(' ', $this->parts);
		} else {
			return '';
		}
	}

}
