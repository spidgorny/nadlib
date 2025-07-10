<?php

use Odbc\Connection;
use Odbc\Result;

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 */
class DBLayerODBC extends DBLayerBase
{

	/**
	 * @var resource|Connection
	 */
	public $connection;

	/**
	 * @var resource|Result
	 */
	public $result;

	public $cursor;

	public function __construct($user, $password, string $host, string $db)
	{
		if ($user) {
			$this->connect($user, $password, $host, $db);
			$this->setQB();
		}
	}

	public function connect($user, $password, string $host, string $db): void
	{
		//$this->connection = odbc_connect('odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME='.$host.';PORT=50000;DATABASE=PCTRANSW;PROTOCOL=TCPIP', $user, $password);
		$this->connection = odbc_pconnect('DSN=' . $host . ';DATABASE=' . $db, $user, $password);
	}

	public function perform($query, array $params = [])
	{
		$this->result = odbc_exec($this->connection, $query);
		if (!$this->result) {
			throw new Exception($this->lastError());
		}

		return $this->result;
	}

	public function lastError(): string
	{
		return 'ODBC error #' . odbc_error() . ': ' . odbc_errormsg();
	}

	public function numRows($res = null): int
	{
		return odbc_num_rows($res);
	}

	public function affectedRows($res = null): void
	{
		// TODO: Implement affectedRows() method.
	}

	public function getTables()
	{
		$this->result = odbc_tables($this->connection);
		return $this->fetchAll($this->result);
	}

	public function escapeBool($value): bool
	{
		return (bool)$value;
	}

	public function free($res): void
	{
		odbc_free_result($res);
	}

	public function lastInsertID($result, $key = null): void
	{
		// TODO: Implement lastInsertID() method.
	}

	public function quoteKey($key)
	{
		return $key;
	}

	public function dataSeek($res, $set): void
	{
		$this->cursor = $set;
	}

	public function fetchAssoc($res): array|false
	{
		if (is_null($this->cursor)) {
			$row = odbc_fetch_array($res);
		} else {
			// row numbering starts with 1 - @see php.net
			$row = odbc_fetch_array($res, 1 + $this->cursor++);
		}

		//debug(__METHOD__, $this->cursor, $row);
		return $row;
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

	public function getPlaceholder($field): void
	{
		// TODO: Implement getPlaceholder() method.
	}

	public function getComment($table, $column)
	{
		// TODO: Implement getComment() method.
	}

	public function getForeignKeys(string $table)
	{
		// TODO: Implement getForeignKeys() method.
	}
}
