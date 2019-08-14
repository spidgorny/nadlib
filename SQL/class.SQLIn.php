<?php

class SQLIn extends SQLWherePart
{

	public $list = array();

	function __construct(array $list)
	{
		$this->list = $list;
	}

	function __toString()
	{
		$field = $this->qb->quoteKey($this->field);
		if (!$field) {
			debug_pre_print_backtrace();
		}
		return $field . " IN (" . implode(", ", $this->qb->quoteValues($this->list)) . ")";
	}

}
