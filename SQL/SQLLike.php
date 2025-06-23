<?php

/**
 * @phpstan-consistent-constructor
 */
class SQLLike extends SQLWherePart
{

	/**
	 * @var string value
	 */
	public $string;
	public $like = 'LIKE';
	public $ilike = 'ILIKE';
	/**
	 * Replace with "%|%" if you like
	 * @var string
	 */
	public $wrap = '|';
	/**
	 * @var bool
	 */
	protected $caseInsensitive;

	/**
	 * @param $string
	 * @param $caseInsensitive
	 */
	public function __construct($string, $caseInsensitive = true)
	{
		parent::__construct();
		$this->caseInsensitive = $caseInsensitive;
		$this->string = $string;
	}

	public static function make($string, $caseInsensitive = false): static
	{
		return new static($string, $caseInsensitive);
	}

	public function wrap($string): static
	{
		$this->wrap = $string;
		return $this;
	}

	public function __toString(): string
	{
		if (!$this->db) {
			throw new InvalidArgumentException(__METHOD__ . ' has to DB');
		}

		$like = $this->caseInsensitive ? $this->ilike : $this->like;
		$w = explode('|', $this->wrap);
		$escape = $this->db->getPlaceholder($this->field);

//		if (false) {
//			$escape = $this->db->escape($this->string);
//			$escape = str_replace('\\"', '"', $escape);
//			$escape = str_replace('%', '\\%', $escape);
//			$escape = str_replace('_', '\\_', $escape);
//		}

		$field = $this->db->quoteKey($this->field);

		if ($this->db->isMySQL()) {
			$sql = sprintf("%s LIKE concat('%s', %s, '%s')", $field, $w[0], $escape, $w[1]);
		} else {
			$sql = $field . " " . $like .
				" '" . $w[0] . "' || " . $escape . " || '" . $w[1] . "'";
		}

		//debug($this->string, $escape, $wrap, $sql); exit();
		return $sql;
	}

	public function getParameter()
	{
		return $this->string;
	}

}
