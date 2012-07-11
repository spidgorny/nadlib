<?php

class SQLBetween extends SQLWherePart {
	protected $start, $end;
	
	/**
	 * @var dbLayerPG
	 */
	protected $db;
	
	function __construct($start, $end) {
		$this->start = $start;
		$this->end = $end;
		$this->db = $GLOBALS['db'];
	}
	
	function toString($field) {
		return $field.' BETWEEN '.$this->db->quoteSQL($this->start).' AND '.$this->db->quoteSQL($this->end);
	}
	
}
