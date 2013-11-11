<?php

class SQLLike extends SQLWherePart {
	protected $string;
	public $like = 'LIKE';

	function __construct($string) {
		parent::__construct();
		$this->string = $string;
	}

	function __toString() {
		return $this->field ." ".$this->like." '%".$this->qb->db->escape($this->string)."%'";
	}

}
