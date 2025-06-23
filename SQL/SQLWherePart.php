<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWherePart
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $sql = '';

	/**
	 * @var string|int
	 */
	protected $field;

	public function __construct($sql = '')
	{
		$this->sql = $sql;
//		$this->db = Config::getInstance()->getDB();
	}

	public function injectDB(DBInterface $db): static
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
		return $this;
	}

	public function injectField($field): static
	{
		$this->field = $field;
		return $this;
	}

	public function debug(): array
	{
		return [
			'class' => get_class($this),
			'sql' => $this->sql,
		];
	}

	/**
	 * Sub-classes should return their parameters
	 */
	public function getParameter()
	{
		return null;
	}

	public function perform()
	{
		return $this->db->perform($this->__toString());
	}

	/**
	 * Not used directly
	 * @throws MustBeStringException
	 * @see SQLWhereEqual
	 */
	public function __toString(): string
	{
		if ($this->field && !is_numeric($this->field)) {
			$part1 = $this->db->quoteWhere(
				[$this->field => $this->sql]
			);
			return implode('', $part1);
		}

		return $this->sql . '';
	}

	public function getField()
	{
		return $this->field;
	}

}
