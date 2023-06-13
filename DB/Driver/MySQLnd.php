<?php

/**
 * Class MySQLnd
 * Wrong approach - MySQLnd is NOT an API
 * @deprecated
 */
class MySQLnd {

	function __construct($db = NULL, $host = '127.0.0.1', $login = 'root', $password = '') {
		$this->db = $db;
		if ($this->db) {
			$this->connect($host, $login, $password);
		}
	}

	function connect($host, $login, $password) {
		$this->connection = @mysqlnd_connect($host, $login, $password);
		if (!$this->connection) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_select_db($this->db, $this->connection);
		if (!$res) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_set_charset('utf8', $this->connection);
		if (!$res) {
			throw new Exception(mysql_error(), mysql_errno());
		}
	}

}
