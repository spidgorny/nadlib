<?php

class dbLayerMS {
	protected $server, $database, $user, $password;
	public $lastQuery;
	protected $connection;
	protected static $instance;

	public static function getInstance() {
		$c = Config::getInstance();
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
		$res = mssql_query($query, $this->connection);
		$msg = mssql_get_last_message();
		if ($msg && $msg != "Changed database context to 'DEV_LOTCHECK'.") {
			debug($msg, $query);
			$this->close();
			$this->connect();
		}
		$this->lastQuery = $query;
		return $res;
	}

	function fetchAssoc($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		return mssql_fetch_assoc($res);
	}

	function fetchAll($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$table = array();
		$rows = mssql_num_rows($res);
		do {
			while (($row = $this->fetchAssoc($res)) != false) {
				$table[] = $row;
			}
		} while (mssql_next_result($res) && $i++ < $rows);
		mssql_free_result($res);
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
	 * @return array ('name' => ...)
	 */
	function getFields($table) {
		$res = $this->perform("SELECT *
FROM syscolumns
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

	/**
	 * Return ALL rows
	 * @param <type> $table
	 * @param <type> $where
	 * @param <type> $order
	 * @return <type>
	 */
	function fetchSelectQuery($table, array $where = array(), $order = '') {
		$res = $this->runSelectQuery($table, $where, $order);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuery($table, array $where, $order = '') {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
		$res = $qb->runSelectQuery($table, $where, $order);
		return $res;
	}

	function fetchSelectQuerySW($table, SQLWhere $where, $order = '') {
		$res = $this->runSelectQuerySW($table, $where, $order);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuerySW($table, SQLWhere $where, $order = '') {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
		$res = $qb->runSelectQuerySW($table, $where, $order);
		return $res;
	}

}
