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

	/**
	 * @ param string $field @ deprecated
	 * @param string $from
	 * @param null $till
	 */
	function __construct($from, $till = NULL) {
		parent::__construct();
		$this->from = $from;
		$this->till = $till;
	}

	function __toString() {
		$field = $this->db->quoteKey($this->field);
		$sql = "($field >= '$this->from'";
		if ($this->till) {
			$sql .= " AND $field < '$this->till'";
		}
		$sql .= ")";
		return $sql;
	}

	function debug() {
		return $this->__toString();
	}

}
