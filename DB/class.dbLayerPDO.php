<?php

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 */
class dbLayerPDO extends dbLayerBase implements DBInterface {

	/**
	 * @var PDO
	 */
	public $connection;

	/**
	 * @var PDOStatement
	 */
	public $result;

	/**
	 * @var string
	 */
	public $dsn;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var null|int
	 */
	protected $dataSeek = NULL;

	function __construct($user = NULL, $password = NULL, $scheme = NULL, $driver = NULL, $host = NULL, $db = NULL) {
		$this->connect($user, $password, $scheme, $driver, $host, $db);
		$this->setQB();
	}

	static function getAvailableDrivers() {
		return PDO::getAvailableDrivers();
	}

	/**
	 * @param $user
	 * @param $password
	 * @param $scheme
	 * @param $driver		IBM DB2 ODBC DRIVER
	 * @param $host
	 * @param $db
	 */
	function connect($user, $password, $scheme, $driver, $host, $db) {
		$dsn = $scheme.':DRIVER={'.$driver.'};DATABASE='.$db.';SYSTEM='.$host.';dbname='.$db.';HOSTNAME='.$host.';aPORT=56789;PROTOCOL=TCPIP;';
		$this->dsn = $scheme.':'.$this->getDSN(array(
			'DRIVER' => '{'.$driver.'}',
			'DATABASE' => $db,
			'SYSTEM' => $host,
			'dbname' => $db,
			'HOSTNAME' => $host,
			'aPORT' => 56789,
			'PROTOCOL' => 'TCPIP',
		));
		//debug($this->dsn);
		$this->connection = new PDO($this->dsn, $user, $password);
	}

	function perform($query, array $params = array()) {
		$this->lastQuery = $query;
		$this->result = $this->connection->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$ok = $this->result->execute($params);
		if (!$ok) {
			throw new Exception(print_r(array(
				'class' => get_class($this),
				'code' => $this->connection->errorCode(),
				'errorInfo' => $this->connection->errorInfo(),
				'query' => $query,
				), true),
				$this->connection->errorCode() ?: 0);
		}
		return $this->result;
	}

	function numRows($res) {
		return $res->rowCount();
	}

	function affectedRows() {
		return $this->res->rowCount();
	}

	function getScheme() {
		$scheme = parse_url($this->dsn);
		$scheme = $scheme['scheme'];
		return $scheme;
	}

	function getTables() {
		$scheme = $this->getScheme();
		if ($scheme == 'mysql') {
			$this->perform('show tables');
		} else if ($scheme == 'odbc') {
			$this->perform('db2 list tables for all');
		}
		return $this->result->fetchAll();
	}

	function lastInsertID() {
		$this->connection->lastInsertId();
	}

	/**
	 * @param PDOStatement $res
	 */
	function free($res) {
		$res->closeCursor();
	}

	function quoteKey($key) {
		return MySQL::quoteKey($key);
	}

	function escapeBool($value) {
		return MySQL::escapeBool($value);
	}

	function __call($method, array $params) {
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_class($this).' and SQLBuilder');
		}
	}

	function fetchAssoc(PDOStatement $res) {
		return $res->fetch(PDO::FETCH_ASSOC);
	}

	function dataSeek($int) {
		$this->dataSeek = $int;
	}

	function fetchAssocSeek(PDOStatement $res) {
		return $res->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $this->dataSeek);
	}

	function getTableColumnsEx($table) {
		$scheme = parse_url($this->dsn);
		$scheme = $scheme['scheme'];
		if ($scheme == 'mysql') {
			$this->perform('show columns from '.$table);
		}
		return $this->fetchAll($this->result, 'Field');
	}

	/**
	 * Avoid this as hell, just for compatibility
	 * @param $str
	 * @return string
	 */
	function escape($str) {
		$quoted = $this->connection->quote($str);
		if ($quoted{0} == "'") {
			$quoted = substr($quoted, 1, -1);
		}
		return $quoted;
	}

	function fetchAll($stringOrRes, $key = NULL) {
		if (is_string($stringOrRes)) {
			$this->perform($stringOrRes);
		}
		$data = $this->result->fetchAll();

		if ($key) {
			$copy = $data;
			$data = [];
			foreach ($copy as $row) {
				$data[$row[$key]] = $row;
			}
		}
		return $data;
	}

	function fetchPartition($res, $start, $limit) {
		if ($this->getScheme() == 'mysql') {
			return $this->fetchPartitionMySQL($res, $start, $limit);
		}
		$data = array();
		for ($i = $start; $i < $start + $limit; $i++) {
			$this->dataSeek($i);
			$row = $this->fetchAssocSeek($res);
			if ($row !== false) {
				$data[] = $row;
			} else {
				break;
			}
		}
		$this->free($res);
		return $data;
	}

	/**
	 * http://stackoverflow.com/questions/15637291/how-use-mysql-data-seek-with-pdo
	 * Will start with 0 and skip rows until $start.
	 * Will end with $start+$limit.
	 * @param $res
	 * @param $start
	 * @param $limit
	 * @return array
	 */
	function fetchPartitionMySQL($res, $start, $limit) {
		$data = array();
		for ($i = 0; $i < $start + $limit; $i++) {
			$row = $this->fetchAssoc($res);
			if ($row !== false) {
				if ($i >= $start) {
					$data[] = $row;
				}
			} else {
				break;
			}
		}
		$this->free($res);
		return $data;
	}

	function uncompress($value) {
		return @gzuncompress(substr($value, 4));
	}

	function transaction() {
		$this->perform('BEGIN');
	}

	function commit() {
		return $this->perform('COMMIT');
	}

	function rollback() {
		return $this->perform('ROLLBACK');
	}

}
