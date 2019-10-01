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
	 * @var string
	 */
	protected $field;

	public function __construct($sql = '')
	{
		$this->sql = $sql;
		$this->db = Config::getInstance()->getDB();
	}

	/**
	 * Not used directly
	 * @return string
	 * @throws MustBeStringException
	 * @see SQLWhereEqual
	 */
	public function __toString()
	{
		//debug(__METHOD__, gettype2($this->db));
		if ($this->field && !is_numeric($this->field)) {
			$part1 = $this->db->quoteWhere(
				array($this->field => $this->sql)
			);
			return '/* SWP */ ' . implode(' /* && */ ', $part1);
		} else {
			return $this->sql . '';
		}
	}

	public function injectDB(DBInterface $db)
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
	}

	public function injectField($field)
	{
		$this->field = $field;
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'db' => get_class($this->db),
			'field' => $this->field,
			$this->field => $this->sql,
		];
	}

	/**
	 * Sub-classes should return their parameters
	 * @return null
	 */
	public function getParameter()
	{
		return NULL;
	}

	public function perform()
	{
		return $this->db->perform((string)$this);
	}

	public function getField()
	{
		return $this->field;
	}

}
