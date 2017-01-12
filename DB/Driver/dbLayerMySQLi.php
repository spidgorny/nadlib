<?php

/**
 * Class MySQLi
 * Should work but it doesn't get num_rows() after store_result().
 */
class dbLayerMySQLi extends dbLayerBase implements DBInterface {

	/**
	 * @var MySQLi
	 */
	var $connection;

	/**
	 * @var array
	 */
	var $columns = [];

	function __construct($db = NULL, $host = '127.0.0.1', $login = 'root', $password = '') {
		$this->database = $db;
		if ($this->database) {
			$this->connect($host, $login, $password);
		}
	}

	function connect($host, $login, $password) {
		$this->connection = new mysqli($host, $login, $password, $this->database);
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
			if ($stmt) {
				$types = str_repeat('s', sizeof($params));
				//			debug($types, $params, $query.'');
				call_user_func_array([$stmt, 'bind_param'],
					array_merge([$types],
						$this->makeValuesReferenced($params)));
				$stmt->execute();
				$stmt->store_result();
				debug($stmt);
				if (method_exists($stmt, 'get_result')) {
					$ok = $stmt->get_result();
				} else {
					$meta = $stmt->result_metadata();
					$data = [];
					while ($field = $meta->fetch_field()) {
//						debug($field);
						$this->columns[$field->name] = &$data[$field->name];
						// pass by reference
					}
					//debug($data, $meta, $field, $this->columns);
					$ok = call_user_func_array(array($stmt, 'bind_result'), $this->columns);
				}
				if (!$ok) {
					throw new DatabaseException(mysqli_error($this->connection));
				}
			} else {
				throw new DatabaseException(mysqli_error($this->connection));
			}
			//debug($query, $params, $stmt->num_rows);
		} else {
			$stmt = $this->connection->query($query);
//			$stmt->fetch_assoc();
		}

		if (!$stmt) {
			debug($query.'', $params);
			throw new DatabaseException($this->connection->error, $this->connection->errno);
		}
		return $stmt;
	}

	function prepare($sql) {
		return mysqli_prepare($this->connection, $sql);
	}

	private function makeValuesReferenced(array $arr){
		$refs = array();
		foreach ($arr as $key => $value) {
			$refs[$key] = &$arr[$key];
		}
		return $refs;

	}

	/**
	 * @param $res	mysqli_result
	 * @return array|false
	 */
	function fetchAssoc($res) {
//		debug(gettype2($res));
		if ($res instanceof mysqli_result) {
			$data = (array)$res->fetch_assoc();
//			debug(gettype2($res), $data);
			return $data;
		} elseif (is_string($res)) {
			$res = $this->perform($res);
			return $res->fetch_assoc();
		} elseif ($res instanceof SQLSelectQuery) {
			$res = $this->perform($res.'', $res->getParameters());
			return $res->fetch_assoc();
		} elseif ($res instanceof mysqli_stmt) {
			$res->fetch();
			return $this->columns;
		} else {
			debug(gettype2($res));
			throw new InvalidArgumentException(__METHOD__);
		}
	}

	function affectedRows($res = NULL) {
		return $this->connection->affected_rows;
	}

	function escape($string) {
		return $this->connection->escape_string($string);
	}

	function escapeBool($value) {
		return intval(!!$value);
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

	function getPlaceholder($field) {
//		$slug = URL::getSlug($field);
//		$slug = str_replace('-', '_', $slug);
//		return '@'.$slug;
		return '?';
	}

	function getInfo() {
		return [
			$this->connection->host_info,
			$this->connection->server_info,
			$this->connection->client_info,
//			$this->connection->get_connection_stats(),
			$this->connection->get_charset(),
		];
	}
	
	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @return string
	 */
	function getReplaceQuery($table, $columns) {
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "REPLACE INTO {$table} ({$fields}) VALUES ({$values})";
		return $q;
	}

}
