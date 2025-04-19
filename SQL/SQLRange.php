<?php

class SQLRange extends SQLWherePart
{

	/**
	 * @var mixed
	 */
	public $from;

	/**
	 * @var mixed|null
	 */
	public $till;

	/**
	 * @ param string $field @ deprecated
	 * @param string $from
	 * @param string $till
	 */
	public function __construct($from, $till = null)
	{
		parent::__construct();
		$this->from = $from;
		$this->till = $till;
	}

	public function __toString(): string
	{
		$field = $this->db->quoteKey($this->field);
		$sql = sprintf("(%s >= '%s'", $field, $this->from);
		if ($this->till) {
			$sql .= sprintf(" AND %s < '%s'", $field, $this->till);
		}

		return $sql . ")";
	}

	public function debug(): array
	{
		return $this->__toString();
	}

}
