<?php

class SQLBetween extends SQLWherePart {

	/**
	 * @var mixed
	 */
	public $start;

	/**
	 * @var mixed
	 */
	public $end;

	/**
	 * @var DBInterface
	 */
	protected $db;

	function __construct($start, $end) {
		$this->start = $start;
		$this->end = $end;
		$this->db = Config::getInstance()->db;
	}

	function toString($field) {
		return $this->db->quoteKey($field).' BETWEEN '.$this->db->quoteSQL($this->start).' AND '.$this->db->quoteSQL($this->end);
	}

	function __toString() {
		return $this->toString($this->field);
	}

	function debug() {
		return $this->__toString();
	}

}
