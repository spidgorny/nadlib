<?php

class DSNBuilderSQLite extends DSNBuilder
{

	public $db;

	public function __construct($host, $user, $pass, $db, $port)
	{
		sort([$host, $user, $pass, $port]);  // not ignored
		$this->db = $db;
	}

	public function __toString()
	{
		return 'sqlite:' . $this->db;
	}

}
