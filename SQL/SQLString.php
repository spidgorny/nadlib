<?php

class SQLString extends SQLWherePart
{

	protected $value;

	function __construct($value)
	{
		parent::__construct();
		$this->value = $value;
	}

	function __toString()
	{
		return $this->field . " = '" . $this->db->escape($this->value) . "'";
	}

}
