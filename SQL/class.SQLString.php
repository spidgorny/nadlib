<?php

class SQLString extends SQLWherePart {

	protected $value;

	function __construct($value) {
		$this->value = $value;
	}

	function __toString() {
		return $this->field ." = '".$this->qb->db->escape($this->value)."'";
	}

}
