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
	 * @var SQLBuilder
	 */
	protected $qb;

	/**
	 * @ param string $field @ deprecated
	 * @param string $from
	 * @param null $till
	 */
	function __construct($from, $till = NULL) {
		$this->from = $from;
		$this->till = $till;
		$this->qb = Config::getInstance()->getQb();
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

	function debug() {
		return $this->__toString();
	}

}
