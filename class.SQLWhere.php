<?php

class SQLWhere {

	protected $parts = array();

	function __construct($where = NULL) {
		if (is_array($where)) {
			$this->parts = $where;
		} else if ($where) {
			$this->parts[] = $where;
		}
	}

	function add($where) {
		$this->parts[] = $where;
	}

	function addArray(array $where) {
		foreach ($where as $el) {
			$this->add($el);
		}
		return $this;
	}

	function __toString() {
		if ($this->parts) {
			return "WHERE\n\t".implode("\n\tAND ", $this->parts);
		} else {
			return '';
		}
	}

	function getAsArray() {
		return $this->parts;
	}

}