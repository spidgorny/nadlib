<?php

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 */
class dbLayerODBC extends dbLayerBase implements DBInterface {

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var resource
	 */
	public $result;

	public $cursor = NULL;

	function __construct($user, $password, $host, $db) {
		if ($user) {
			$this->connect($user, $password, $host, $db);
			$this->setQB();
		}
	}

	function connect($user, $password, $host, $db) {
		//$this->connection = odbc_connect('odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME='.$host.';PORT=50000;DATABASE=PCTRANSW;PROTOCOL=TCPIP', $user, $password);
		$this->connection = odbc_pconnect('DSN='.$host.';DATABASE='.$db, $user, $password);
	}

	function perform($query) {
		if ($this->connection) {
			$this->result = odbc_exec($this->connection, $query);
		} else {
			throw new Exception($this->lastError());
		}
		return $this->result;
	}

	function numRows($res) {
		return odbc_num_rows($res);
	}

	function affectedRows() {
		// TODO: Implement affectedRows() method.
	}

	function getTables() {
		$this->result = odbc_tables($this->connection);
		return $this->fetchAll($this->result);
	}

	function escapeBool($value) {
		return !!$value;
	}

	function free($res) {
		if (is_resource($res)) {
			odbc_free_result($res);
		}
	}

	function lastInsertID() {
		// TODO: Implement lastInsertID() method.
	}

	function quoteKey($key) {
		return $key;
	}

	function dataSeek($res, $set) {
		$this->cursor = $set;
	}

	function fetchAssoc($res) {
		if ($this->connection) {
			if (is_null($this->cursor)) {
				return odbc_fetch_array($res);
			} else {
				// row numbering starts with 1 - @see php.net
				return odbc_fetch_array($res, 1+$this->cursor++);
			}
		} else {
			throw new Exception(__METHOD__);
		}
	}

	function __call($method, array $params) {
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_class($this).' and SQLBuilder');
		}
	}

	function lastError() {
		return 'ODBC error #'.odbc_error().': '.odbc_errormsg();
	}

}
