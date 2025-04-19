<?php

class SQLDateTime extends Time
{

	public function __toString(): string
	{
		return $this->format('Y-m-d H:i:s');
	}

}
