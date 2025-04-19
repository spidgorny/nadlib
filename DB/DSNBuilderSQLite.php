<?php

class DSNBuilderSQLite extends DSNBuilder
{

	public $host;
    
	public $user;
    
	public $pass;
    
	public $db;
    
	public $port;

	public function __construct($host, $user, $pass, $db, $port)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;
		$this->port = $port;
	}

	public function __toString(): string
	{
		return 'sqlite:' . $this->db;
	}

}
