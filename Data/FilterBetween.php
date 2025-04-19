<?php

/**
 * Class FilterBetween - used by ArrayPlus::filterBy() to
 * filter dates between specific interval
 */
class FilterBetween
{

	public $from;

	public $till;

	public function __construct($from, $till)
	{
		$this->from = $from;
		$this->till = $till;
	}

	public function apply($value): bool
	{
		//		var_dump($ok, $this->from, $value, $this->till);
		return ($this->from <= $value) && ($value <= $this->till);
	}

}
