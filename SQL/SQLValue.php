<?php

class SQLValue
{

	protected $value;

	public function __construct($value = null)
	{
//		parent::__construct($value);
		$this->value = $value;
	}

	public function __toString()
	{
		return '$0$';
	}

	public function getParameter()
	{
		return $this->value;
	}

}
