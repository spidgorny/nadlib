<?php

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 */
class dbLayerODBC implements DBInterface {

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var resource
	 */
	public $result;

	function __construct($user, $password, $host) {
		if ($user) {
			$this->connect($user, $password, $host);
		}
	}

	function connect($user, $password, $host) {
		$this->connection = odbc_connect('odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME='.$host.';PORT=50000;DATABASE=PCTRANSW;PROTOCOL=TCPIP', $user, $password);
	}

	function perform($query) {
		$this->result = odbc_exec($this->connection, $query);
	}

	function numRows($res) {
		// TODO: Implement numRows() method.
	}

	function affectedRows() {
		// TODO: Implement affectedRows() method.
	}

	function getTables() {
		$this->result = odbc_tables($this->connection);
		return $this->fetchAll($this->result);
	}

	function escapeBool($value) {
		// TODO: Implement escapeBool() method.
	}

	function free($res) {
		// TODO: Implement free() method.
	}

	function lastInsertID() {
		// TODO: Implement lastInsertID() method.
	}

	function quoteKey($key) {
		// TODO: Implement quoteKey() method.
	}

}
