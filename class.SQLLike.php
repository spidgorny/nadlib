<?php

class SQLLike extends SQLWherePart {
	protected $string;

	function __construct($string) {
		$this->string = $string;
	}

	function __toString() {
		return $this->field ." LIKE '%".$this->qb->db->escape($this->string)."%'";
	}

}
