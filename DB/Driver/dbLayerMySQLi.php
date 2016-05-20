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
	 * @param       $query
	 * @param array $params
	 * @return bool|mysqli_result
	 * @throws DatabaseException
	 */
	function perform($query, array $params = array()) {
		$this->lastQuery = $query;

		if ($params) {
			$stmt = $this->prepare($query);
			foreach ($params as $k => $v) {
				$stmt->bind_param('s', $v);
			}
			$stmt->execute();
			$ok = $stmt->get_result();
			//debug($query, $params, $stmt->num_rows);
		} else {
			$ok = $this->connection->query($query);
		}
		
		if (!$ok) {
			debug($query.'', $params);
			throw new DatabaseException($this->connection->error, $this->connection->errno);
		}
		return $ok;
	}

	function prepare($sql) {
		return mysqli_prepare($this->connection, $sql);
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

	/**
	 * @param $res mysqli_result
	 * @param null $table
	 * @return mixed
	 */
	function lastInsertID($res, $table = NULL) {
		return $this->connection->insert_id;
	}

	/**
	 * @param mysqli_result $res
	 * @return mixed
	 */
	function numRows($res = NULL) {
		//debug($res->num_rows);
		return $res->num_rows;
	}

}
