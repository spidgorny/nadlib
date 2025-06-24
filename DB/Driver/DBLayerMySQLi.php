<?php

/**
 * Class MySQLi
 * Should work but it doesn't get num_rows() after store_result().
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayerMySQLi extends DBLayerBase implements DBInterface
{

	/**
	 * @var MySQLi
	 */
	public $connection;

	/**
	 * @var array
	 */
	public $columns = [];

	public function __construct($db = null, $host = '127.0.0.1', $login = 'root', $password = '')
	{
		$this->dbName = $db;
		if ($this->dbName) {
			$this->connect($host, $login, $password);
		}
	}

	public function connect($host, $login, $password): void
	{
		$this->connection = new mysqli($host, $login, $password, $this->dbName);
		if (!$this->connection) {
			throw new \RuntimeException(mysqli_error($this->connection), mysqli_errno($this->connection));
		}

		$this->connection->set_charset('utf8');
	}

	/**
	 * @param resource|mysqli_result|string|SQLSelectQuery|mysqli_stmt $res
	 * @return array|false
	 * @throws DatabaseException
	 */
	public function fetchAssoc($res)
	{
		//		debug(gettype2($res));
		if ($res instanceof mysqli_result) {
			//			debug(gettype2($res), $data);
			return (array)$res->fetch_assoc();
		}

		if (is_string($res)) {
			$res = $this->perform($res);
			return $res->fetch_assoc();
		}

		if ($res instanceof SQLSelectQuery) {
			$res = $this->perform($res . '', $res->getParameters());
			return $res->fetch_assoc();
		}

		if ($res instanceof mysqli_stmt) {
			$res->fetch();
			return $this->columns;
		}

		debug(typ($res));
		throw new InvalidArgumentException(__METHOD__);
	}

	/**
	 * @param       $query
	 * @return bool|mysqli_result
	 * @throws DatabaseException
	 */
	public function perform($query, array $params = [])
	{
		$this->lastQuery = $query;

		if ($params !== []) {
			$stmt = $this->prepare($query);
			if ($stmt) {
				$types = str_repeat('s', count($params));
				//			debug($types, $params, $query.'');
				$stmt->bind_param(...array_merge(
					[$types],
					$this->makeValuesReferenced($params)
				));
				$stmt->execute();
				$stmt->store_result();
				debug($stmt);
				$ok = $stmt->get_result();

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
			debug($query . '', $params);
			throw new DatabaseException($this->connection->error, $this->connection->errno);
		}

		return $stmt;
	}

	public function prepare($sql): \mysqli_stmt|false
	{
		return mysqli_prepare($this->connection, $sql);
	}

	/**
	 * @return mixed[]
	 */
	private function makeValuesReferenced(array $arr): array
	{
		$refs = [];
		foreach (array_keys($arr) as $key) {
			$refs[$key] = &$arr[$key];
		}

		return $refs;
	}

	public function affectedRows($res = null)
	{
		return $this->connection->affected_rows;
	}

	public function escape($string): string
	{
		return $this->connection->escape_string($string);
	}

	public function escapeBool($value): int
	{
		return intval((bool)$value);
	}

	/**
	 * @param resource $res
	 * @return mixed
	 */
	public function lastInsertID($res, $table = null)
	{
		return $this->connection->insert_id;
	}

	/**
	 * @param mysqli_result $res
	 * @return mixed
	 */
	public function numRows($res = null)
	{
		//debug($res->num_rows);
		return $res->num_rows;
	}

	public function getPlaceholder($field): string
	{
		//		$slug = ?URL::getSlug($field);
		//		$slug = str_replace('-', '_', $slug);
		//		return '@'.$slug;
		return '?';
	}

	public function getInfo(): array
	{
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
	 */
	public function getReplaceQuery($table, $columns): string
	{
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		return sprintf('REPLACE INTO %s (%s) VALUES (%s)', $table, $fields, $values);
	}

	public function getVersion(): void
	{
		// TODO: Implement getVersion() method.
	}

	public function __call($method, array $params)
	{
		// TODO: Implement @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runDeleteQuery($table, array $where)
	}
}
