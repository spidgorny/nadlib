<?php

class SQLRange extends SQLWherePart {
	/**
	 * @var mixed
	 */
	public $from;

	/**
	 * @var mixed|null
	 */
	public $till;

	function __construct($field, $from, $till = NULL) {
		$this->field = $field;
		$this->from = $from;
		$this->till = $till;
	}

	function __toString() {
		$field = $this->qb->quoteKey($this->field);
		$sql = "($field >= '$this->from'";
		if ($this->till) {
			$sql .= " AND $field < '$this->till'";
		}
		$sql .= ")";
		return $sql;
	}

}
