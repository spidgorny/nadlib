<?php

class SQLTime
{
	protected \Time $time;

	public function __construct(Time $t)
	{
		$this->time = $t;
	}

	public function __toString(): string
	{
		return $this->time->format('Y-m-d H:i:s');
	}

}
