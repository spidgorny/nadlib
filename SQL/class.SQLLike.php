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

	/**
	 * Replace with "%|%" if you like
	 * @var string
	 */
	public $wrap = '|';

	function __construct($string, $caseInsensitive = false) {
		parent::__construct();
        $this->caseInsensitive = $caseInsensitive;
		$this->string = $string;
	}

	function __toString() {
		$like = $this->caseInsensitive ? $this->ilike : $this->like;
		$w = explode('|', $this->wrap);
		$wrap = $w[0] . $this->db->escape($this->string) . $w[1];
		return $this->field ." ". $like ." '".$wrap."'";
	}

	static function make($string, $caseInsensitive = false) {
		return new self($string, $caseInsensitive);
	}

}
