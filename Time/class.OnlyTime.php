<?php

class OnlyTime extends Time
{

	function __construct($input = NULL, $relativeTo = NULL)
	{
		parent::__construct($input, $relativeTo);
		$this->modify('1970-01-01 H:i:s');
		$this->updateDebug();
		//debug($this);
	}

	function getMySQL()
	{
		return gmdate('H:i:s', $this->time);
	}

}
