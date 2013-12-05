<?php

class SQLIn extends SQLWherePart {
	public $list = array();

	function __construct(array $list) {
		$this->list = $list;
	}

	function __toString() {
		$qb = Config::getInstance()->qb;
		$field = $qb->quoteKey($this->field);
		if (!$field) {
			debug_pre_print_backtrace();
		}
		return $field ." IN (".implode(", ", $qb->quoteValues($this->list)).")";
	}

}
