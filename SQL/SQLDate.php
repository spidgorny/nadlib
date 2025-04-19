<?php

class SQLDate extends Date
{

	protected \Date $date;

	public function __construct(Date $d)
	{
		$this->date = $d;
	}

	public function __toString(): string
	{
		return $this->date->format('Y-m-d');
	}

	public function debug(): string
	{
		return $this->__toString();
	}

}
