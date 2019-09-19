<?php

/**
 * Class FilterBetween - used by ArrayPlus::filterBy() to
 * filter dates between specific interval
 */
class FilterBetween
{

	public $from;

	public $till;

	function __construct($from, $till)
	{
		$this->from = $from;
		$this->till = $till;
	}

	function apply($value)
	{
		$ok = ($this->from <= $value) && ($value <= $this->till);
//		var_dump($ok, $this->from, $value, $this->till);
		return $ok;
	}

}
