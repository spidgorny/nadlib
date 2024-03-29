<?php

/**
 * Class dbLayerODBC
 * @mixin SQLBuilder
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 */
class DBLayerODBC extends DBLayerBase implements DBInterface
{

	/**
	 * @var resource
	 */
	public $connection;

	/**
	 * @var resource
	 */
	public $result;

	public $cursor = NULL;

	function __construct($user, $password, $host, $db)
	{
		if ($user) {
			$this->connect($user, $password, $host, $db);
			$this->setQB();
		}
	}

	function connect($user, $password, $host, $db)
	{
		//$this->connection = odbc_connect('odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME='.$host.';PORT=50000;DATABASE=PCTRANSW;PROTOCOL=TCPIP', $user, $password);
		$this->connection = odbc_pconnect('DSN=' . $host . ';DATABASE=' . $db, $user, $password);
	}

	public function perform($query, array $params = [])
	{
		if ($this->connection) {
			$this->result = odbc_exec($this->connection, $query);
			if (!$this->result) {
				throw new Exception($this->lastError());
			}
		} else {
			throw new Exception($this->lastError());
		}
		return $this->result;
	}

	function numRows($res = null)
	{
		return odbc_num_rows($res);
	}

	function affectedRows($res = NULL)
	{
		// TODO: Implement affectedRows() method.
	}

	function getTables()
	{
		$this->result = odbc_tables($this->connection);
		return $this->fetchAll($this->result);
	}

	function escapeBool($value)
	{
		return !!$value;
	}

	function free($res)
	{
		if (is_resource($res)) {
			odbc_free_result($res);
		}
	}

	function lastInsertID($result, $key = NULL)
	{
		// TODO: Implement lastInsertID() method.
	}

	function quoteKey($key)
	{
		return $key;
	}

	function dataSeek($res, $set)
	{
		$this->cursor = $set;
	}

	function fetchAssoc($res)
	{
		if ($this->connection) {
			if (is_null($this->cursor)) {
				$row = odbc_fetch_array($res);
			} else {
				// row numbering starts with 1 - @see php.net
				$row = odbc_fetch_array($res, 1 + $this->cursor++);
			}
			//debug(__METHOD__, $this->cursor, $row);
			return $row;
		} else {
			throw new Exception(__METHOD__);
		}
	}

	function lastError()
	{
		return 'ODBC error #' . odbc_error() . ': ' . odbc_errormsg();
	}

	public function getVersion()
	{
		// TODO: Implement getVersion() method.
	}

	public function __call($name, $arguments)
	{
		// TODO: Implement @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
		// TODO: Implement @method  runDeleteQuery($table, array $where)
	}
}
