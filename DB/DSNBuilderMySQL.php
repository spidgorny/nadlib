<?php

class DSNBuilderMySQL extends DSNBuilder {

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
			'DATABASE' => $this->db,
			'host' => $this->host,
			'SYSTEM' => $this->host,
			'dbname' => $this->db,
			'HOSTNAME' => $this->host,
			'PORT' => $this->port,
			'PROTOCOL' => 'TCPIP',
		);
		return 'mysql:'.$this->getDSN($aDSN);
	}

}
