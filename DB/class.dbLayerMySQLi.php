<?php

/**
 * Class MySQLi
 * Wrong approach
 */
class dbLayerMySQLi extends dbLayerBase implements DBInterface {

	/**
	 * @var MySQLi
	 */
	var $connection;

	function __construct($db = NULL, $host = '127.0.0.1', $login = 'root', $password = '') {
		$this->database = $db;
		if ($this->database) {
			$this->connect($host, $login, $password);
		}
	}

	function connect($host, $login, $password) {
		$this->connection = new MySQLi($host, $login, $password, $this->database);
		if (!$this->connection) {
			throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
		}
		$this->connection->set_charset('utf8');
	}

	/**
	 * @param $query
	 * @return bool|mysqli_result
	 */
	function perform($query) {
		$this->lastQuery = $query;
		return $this->connection->query($query);
	}

	/**
	 * @param $res	mysqli_result
	 * @return array|false
	 */
	function fetchAssoc($res) {
		if ($res instanceof mysqli_result) {
			return $res->fetch_assoc();
		} else {
			return NULL;
		}
	}

	function affectedRows($res = NULL) {
		return $this->connection->affected_rows;
	}

	function escape($string) {
		return $this->connection->escape_string($string);
	}

}
