<?php

class DSNBuilderSQLite extends DSNBuilder
{

	public $db;

	public function __construct($host, $user, $pass, $db, $port)
	{
		$dummy = [$host, $user, $pass, $port];
		sort($dummy);  // not ignored, phpstan
		$this->db = $db;
	}

	public function __toString()
	{
		return 'sqlite:' . $this->db;
	}

}
