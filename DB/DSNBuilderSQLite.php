<?php

class DSNBuilderSQLite extends DSNBuilder
{

	var $db;

	function __construct($host, $user, $pass, $db, $port)
	{
		$this->db = $db;
	}

	function __toString()
	{
		return 'sqlite:' . $this->db;
	}

}
