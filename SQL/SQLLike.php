<?php

class SQLLike extends SQLWherePart
{

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

	public function __construct($string, $caseInsensitive = true)
	{
		parent::__construct();
		$this->string = $string;
		$this->caseInsensitive = $caseInsensitive;
	}

	public function wrap($string)
	{
		$this->wrap = $string;
		return $this;
	}

	public function __toString()
	{
		if (!$this->db) {
			throw new InvalidArgumentException(__METHOD__ . ' has to DB');
		}

		$like = $this->caseInsensitive ? $this->ilike : $this->like;
		$w = explode('|', $this->wrap);

		// must get from QB, to have the index starting with $1
		$escape = $this->db->qb->getPlaceholder($this->field);

		$field = $this->db->quoteKey($this->field);

		if ($this->db->isMySQL()) {
			$sql = "$field LIKE concat('{$w[0]}', {$escape}, '{$w[1]}')";
		} else {
			$sql = $field . " " . $like .
				" '" . $w[0] . "' || " . $escape . " || '" . $w[1] . "'";
		}
		//debug($this->string, $escape, $wrap, $sql); exit();
		return $sql;
	}

	public static function make($string, $caseInsensitive = false)
	{
		return new static($string, $caseInsensitive);
	}

	public function getParameter()
	{
		return $this->string;
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'string' => $this->string,
			'caseInsensitive' => $this->caseInsensitive,
			'wrap' => $this->wrap,
		];
	}

}
