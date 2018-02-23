<?php

class DSNBuilderPostgreSQL extends DSNBuilder {

	var $host;
	var $user;
	var $pass;
	var $db;
	var $port;

	function __construct($host, $user, $pass, $db, $port = 3306)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;
		$this->port = $port;
	}

	function __toString()
	{
		$aDSN = array(
			'host' => $this->host,
			'dbname' => $this->db,
			'port' => $this->port,
		);
		return 'pgsql:'.$this->getDSN($aDSN);
	}

}
