<?php

class SQLIn extends SQLWherePart {
	public $list = array();

	function __construct(array $list) {
		$this->list = $list;
	}

	function __toString() {
		$qb = Config::getInstance()->qb;
		$field = $qb->quoteKey($this->field);
		return $field ." IN ('".implode("', '", $this->list)."')";
	}

}
