<?php

class SQLGroup {

	var $db;

	protected $parts = array();

	function __construct($order = array()) {
		if (is_array($order)) {
			$this->parts = $order;
		} elseif ($order) {
			$this->parts[] = str_replace('GROUP BY', '', $order);
		}
	}

	function __toString() {
		if ($this->parts) {
			return 'GROUP BY '.implode(' ', $this->parts);
		} else {
			return '';
		}
	}

}
