<?php

class DSNBuilderSQLite extends DSNBuilder {

	var $db;

	function __construct($db)
	{
		$this->db = $db;
	}

	function __toString()
	{
		return 'sqlite:'.$this->db;
	}

}
