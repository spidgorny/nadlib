<?php

class DatabaseException extends Exception
{

	public $query;

	function setQuery($q)
	{
		$this->query = $q;
	}

	function getQuery()
	{
		return $this->query;
	}

}
