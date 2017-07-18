<?php

class dbLayerMS implements DBInterface {

	/**
	 * @var string
	 */
	protected $server, $database, $user, $password;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var resource
	 */
	protected $connection;

	/**
	 * @var dbLayerMS
	 */
	protected static $instance;

	/**
	 * Will output every query
	 * @var bool
	 */
	public $debug = false;

	/**
	 * In MSSQL mssql_select_db() is returning the following as error messages
	 * @var array
	 */
	public $ignoreMessages = array(
		"Changed database context to 'DEV_LOTCHECK'.",
		"Changed database context to 'PRD_LOTCHECK'.",
	);

	public static function getInstance() {
		//$c = Config::getInstance();
		//if (!self::$instance) self::$instance = ;
		return self::$instance;
	}

	function  __construct($server, $database, $user, $password) {
		$this->server = $server;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
		$this->connect();
	}

	function connect() {
		$this->connection = mssql_connect($this->server, $this->user, $this->password);
		mssql_select_db($this->database);
	}

	function close() {
		mssql_close($this->connection);
	}

	function perform($query, array $arguments = array()) {
		foreach ($arguments as $ar) {
			$query = str_replace('?', $ar, $query);
		}
		$profiler = new Profiler();
		$res = @mssql_query($query, $this->connection);
		$msg = mssql_get_last_message();
		if ($this->debug) {
			debug(array(
				'method' => __METHOD__,
				'query' => $query,
				is_resource($res)
					? $this->numRows($res)
					: ($res ? 'TRUE' : 'FALSE'),
				'elapsed' => $profiler->elapsed(),
				'msg' => $msg,
			));
		}
		if ($msg && !in_array($msg, $this->ignoreMessages)) {
			//debug($msg, $query);
			$msg2 = mssql_fetch_assoc(mssql_query('SELECT @@ERROR AS ErrorCode', $this->connection))['ErrorCode'];
			$this->close();
			$this->connect();
			debug($query);
			throw new Exception(__METHOD__.': '.$msg.BR.$query.BR.$msg2);
		}
		$this->lastQuery = $query;
		return $res;
	}

	function fetchAssoc($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		} if (!is_resource($res)) {
			debug($res);
		}
		return mssql_fetch_assoc($res);
	}

	function fetchAll($res, $keyKey = NULL) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$table = array();
		$rows = mssql_num_rows($res);
		$i = 0;
		do {
			while (($row = $this->fetchAssoc($res)) != false) {
				if ($keyKey) {
					$key = $row[$keyKey];
					$table[$key] = $row;
				} else {
					$table[] = $row;
				}
			}
		} while (mssql_next_result($res) && $i++ < $rows);
		$this->free($res);
		return $table;
	}

	/**
	 *
	 * @return array ('name' => ...)
	 */
	function getTables() {
		$res = $this->perform("select * from sysobjects where xtype = 'U'");
		$tables = $this->fetchAll($res);
		return $tables;
	}

	/**
	 *
	 * @param $table
	 * @return array ('name' => ...)
	 */
	function getFields($table) {
		//mssql_meta - doesn't exist
		$res = $this->perform("
SELECT
	syscolumns.name,
	systypes.name AS stype,
	syscolumns.*
FROM syscolumns
LEFT OUTER JOIN systypes ON (systypes.xtype = syscolumns.xtype)
WHERE id = (SELECT id
FROM sysobjects
WHERE type = 'U'
AND name = '?')", array($table));
		$tables = $this->fetchAll($res);
		return $tables;
	}

	function escape($val) {
		return $this->mssql_escape_string($val);
	}

	function mssql_escape_string($data) {
        if ( !isset($data) or empty($data) ) return '';
        if ( is_numeric($data) ) return $data;

        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ( $non_displayables as $regex )
            $data = preg_replace( $regex, '', $data );
        $data = str_replace("'", "''", $data );
        return $data;
    }

	/* *
	 * Return ALL rows
	 * @param <type> $table
	 * @param <type> $where
	 * @param <type> $order
	 * @return <type>
	 * /
	function fetchSelectQuery($table, array $where = array(), $order = '') {
		$res = $this->runSelectQuery($table, $where, $order);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuery($table, array $where, $order = '') {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuery($table, $where, $order);
		return $res;
	}

	function fetchSelectQuerySW($table, SQLWhere $where, $order = '') {
		$res = $this->runSelectQuerySW($table, $where, $order);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuerySW($table, SQLWhere $where, $order = '') {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuerySW($table, $where, $order);
		return $res;
	}
*/
	function numRows($res) {
		return mssql_num_rows($res);
	}

	function quoteKey($key) {
		return '['.$key.']';
	}

	function lastInsertID() {
		$lq = $this->lastQuery;
		$query = 'SELECT SCOPE_IDENTITY()';
		$res = $this->perform($query);
		$row = $this->fetchAssoc($res);
		//debug($lq, $row);
		$val = $row['computed'];
		return $val;
	}

	function __call($method, array $params) {
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_class($this).' and SQLBuilder');
		}
	}

	function free($res) {
		mssql_free_result($res);
	}

	function escapeBool($value) {
		return $value ? 1 : 0;
	}

	function affectedRows() {
		return mssql_rows_affected($this->connection);
	}

}
