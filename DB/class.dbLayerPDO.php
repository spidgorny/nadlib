<?php

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 */
class dbLayerPDO implements DBInterface {

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var resource
	 */
	public $result;

	function __construct($user = NULL, $password = NULL, $host = NULL, $db = NULL) {
		$this->connect($user, $password, $host, $db);
	}

	static function getAvailableDrivers() {
		return PDO::getAvailableDrivers();
	}

	function connect($user, $password, $host, $db) {
		//$this->connection = new PDO('odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME='.$host.';PORT=50000;DATABASE='.$db.';PROTOCOL=TCPIP;', $user, $password);
		$this->connection = new PDO('ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE='.$db.'; HOSTNAME='.$host.';aPORT=56789;PROTOCOL=TCPIP;', $user, $password);
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
