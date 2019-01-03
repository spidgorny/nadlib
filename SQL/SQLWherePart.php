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
	 * @see SQLWhereEqual
	 * @return string
	 * @throws MustBeStringException
	 */
	public function __toString()
	{
		//debug(__METHOD__, gettype2($this->db));
		if ($this->field && !is_numeric($this->field)) {
			if ($this->field == 'read') {
				//debug($this->field, $this->sql);
			}
			$part1 = $this->db->quoteWhere(
				array($this->field => $this->sql)
			);
			return implode('', $part1);
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
		return $this->__toString();
	}

	/**
	 * Sub-classes should return their parameters
	 * @return null
	 */
	public function getParameter()
	{
		return null;
	}

	public function perform()
	{
		return $this->db->perform($this->__toString());
	}

	public function getField()
	{
		return $this->field;
	}

}
