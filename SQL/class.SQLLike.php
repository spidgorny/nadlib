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

	function __construct($string, $caseInsensitive = true) {
		parent::__construct();
        $this->caseInsensitive = $caseInsensitive;
		$this->string = $string;
	}

	function __toString() {
		$like = $this->caseInsensitive ? $this->ilike : $this->like;
		$w = explode('|', $this->wrap);
		if (true) {
			$escape = '$0$';
		} else {
			$escape = $this->db->escape($this->string);
			$escape = str_replace('\\"', '"', $escape);
			$escape = str_replace('%', '\\%', $escape);
			$escape = str_replace('_', '\\_', $escape);
		}

		if ($this->db->isMySQL()) {
			$sql = "{$this->field} LIKE concat('{$w[0]}', {$escape}, '{$w[1]}')";
		} else {
			$sql = $this->field . " " . $like .
				" '" . $w[0] . "' || " . $escape . " || '" . $w[1] . "'";
		}
		//debug($this->string, $escape, $wrap, $sql); exit();
		return $sql;
	}

	static function make($string, $caseInsensitive = false) {
		return new static($string, $caseInsensitive);
	}

	function getParameter() {
		return $this->string;
	}

}
