<?php

class SQLHaving extends SQLWhere {

	protected $parts = array();

	function __construct($order = array()) {
		if (is_array($order)) {
			$this->parts = $order;
		} else if ($order) {
			$this->parts[] = $order;
		}
	}

	function __toString() {
		if ($this->parts) {
			return 'ORDER BY '.implode(' ', $this->parts);
		} else {
			return '';
		}
	}

}
