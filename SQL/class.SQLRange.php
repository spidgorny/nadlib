<?php

class SQLRange extends SQLWherePart {
	public $field, $from, $till;

	function __construct($field, $from, $till) {
		$this->field = $field;
		$this->from = $from;
		$this->till = $till;
	}

	function __toString() {
		$qb = Config::getInstance()->qb;
		$field = $qb->quoteKey($this->field);
		return "($field >= '$this->from' AND $field < '$this->till')";
	}

}