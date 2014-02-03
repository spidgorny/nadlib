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
		$dsn = $scheme.':'.$this->getDSN(array(
			'DRIVER' => '{'.$driver.'}',
			'DATABASE' => $db,
			'SYSTEM' => $host,
			'dbname' => $db,
			'HOSTNAME' => $host,
			'aPORT' => 56789,
			'PROTOCOL' => 'TCPIP',
		));
		debug($dsn);
		$this->connection = new PDO($dsn, $user, $password);
	}

	function perform($query, $flags = PDO::FETCH_ASSOC) {
		$this->result = $this->connection->query($query, $flags);
		if (!$this->result) {
			$error = implode(BR, $this->connection->errorInfo());
			debug($query, $error);
			throw new Exception(
				$error,
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

	function getTables() {
		$this->perform('show tables');
		return $this->result->fetchAll();
	}

	function lastInsertID() {
		$this->connection->lastInsertId();
	}

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
		$qb = Config::getInstance()->qb;
		//debug_pre_print_backtrace();
		//debug($method, $params);
		if (method_exists($qb, $method)) {
			return call_user_func_array(array($qb, $method), $params);
		} else {
			throw new Exception($method.' not found in MySQL and SQLBuilder');
		}
	}

}
