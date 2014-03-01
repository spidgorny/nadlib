<?php

class SQLLike extends SQLWherePart {

	/**
	 * @var string value
	 */
	public $string;

	/**
	 * @var bool
	 */
	protected $caseInsensitive;

	public $like = 'LIKE';

	public $ilike = 'ILIKE';

	function __construct($string, $caseInsensitive = false) {
		parent::__construct();
        $this->caseInsensitive = $caseInsensitive;
		$this->string = $string;
	}

	function __toString() {
		$like = $this->caseInsensitive ? $this->ilike : $this->like;
		return $this->field ." ". $like ." '%".$this->qb->db->escape($this->string)."%'";
	}

}
