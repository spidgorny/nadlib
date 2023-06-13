<?php

class DatabaseException extends Exception
{

	var $query;

	function setQuery($q)
	{
		$this->query = $q;
	}

	function getQuery()
	{
		return $this->query;
	}

}
