<?php

class SQLLike extends SQLWherePart {
	protected $string;
	protected $caseInsensitive;
	public $like = 'LIKE';
	public $ilike = 'ILIKE';

	function __construct($string, $caseInsensitive = false) {
		parent::__construct();
        $this->caseInsensitive = $caseInsensitive;
		$this->string = $string;
	}

	function __toString() {
		return $this->field ." ". ($this->caseInsensitive ? $this->ilike : $this->like) ." '%".$this->qb->db->escape($this->string)."%'";
	}

}
