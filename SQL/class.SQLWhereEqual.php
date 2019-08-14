<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWhereEqual extends SQLWherePart
{

	/**
	 * $this->field is inherited
	 * @var
	 */
	protected $val;

	/**
	 *
	 * @var dbLayerPG
	 */
	protected $db;

	function __construct($field, $val)
	{
		$this->field = $field;
		$this->val = $val;
		$this->db = Config::getInstance()->db;
	}

	function __toString()
	{
		if (is_numeric($this->val)) {    // leading 0 leads to problems
			$sql = "({$this->field} = " . $this->val . " OR {$this->field} = '" . $this->val . "')";
		} elseif (is_null($this->val)) {
			$sql = $this->field . ' IS NULL';
		} else {
			$sql = $this->field . ' = ' . $this->db->quoteSQL($this->val);
		}
		return $sql;
	}

	function debug()
	{
		return $this->__toString();
	}

}
