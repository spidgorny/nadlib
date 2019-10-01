<?php

class SQLFunction extends SQLValue
{

	protected $function;
	protected $inside;

	public function __construct($function, $inside)
	{
//		parent::__construct($value);
		$this->function = $function;
		$this->inside = $inside;
	}

	public function __toString()
	{
		return $this->function.'('.$this->inside.')';
	}

	public function getParameter()
	{
		if (!is_object($this->inside)) {
			return null;
		}
		if (!method_exists($this->inside, 'getParameter')) {
			return null;
		}
		return $this->inside->getParameter();
	}

}
