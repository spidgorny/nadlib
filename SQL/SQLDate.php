<?php

class SQLDate extends Date
{

	public function __toString(): string
	{
		return $this->format('Y-m-d');
	}

	public function debug(): string
	{
		return $this->__toString();
	}

}
