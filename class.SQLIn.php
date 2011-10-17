<?php

class SQLIn extends SQLWherePart {
	public $field, $list = array();

	function __construct($field, array $list) {
		$this->field = $field;
		$this->list = $list;
	}

	function __toString() {
		$qb = Config::getInstance()->qb;
		$field = $qb->quoteKey($this->field);
		return "$field IN ('".implode("', '", $this->list)."')";
	}

}
