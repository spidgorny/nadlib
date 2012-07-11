<?php

class MySQL {
	public $db;
	public $lastQuery;
	protected $connection;
	public $queryLog = array();		// set to NULL for disabling

	function __construct($db = '', $host = '127.0.0.1', $login = 'root', $password = '') {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db = $db;
		if ($this->db) {
			$this->connect($db, $host, $login, $password);
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function connect($db, $host, $login, $password) {
		ini_set('mysql.connect_timeout', 1);
		$this->connection = mysql_pconnect($host, $login, $password);
		if (!$this->connection) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_select_db($this->db);
		if (!$res) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_set_charset('utf8');
		if (!$res) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		//debug(mysql_client_encoding()); exit();
	}

	function getCaller($stepBack = 3) {
		$btl = debug_backtrace();
		reset($btl);
		for ($i = 0; $i < $stepBack; $i++) {
			$bt = next($btl);
		}
		if ($bt['function'] == 'runSelectQuery') {
			$bt = next($btl);
		}
		return "{$bt['class']}::{$bt['function']}";
	}

	function perform($query) {
		$c = 2;
		do {
			$caller = $this->getCaller($c);
			$c++;
		} while (in_array($caller, array(
			'MySQL::fetchSelectQuery',
			'OODBase::findInDB',
			'FlexiTable::findInDB',
		)));
		$profilerKey = __METHOD__." (".$caller.")";
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer($profilerKey);
		$start = microtime(true);
		$res = @mysql_query($query);
		if (!is_null($this->queryLog)) {
			$this->queryLog[$query] += microtime(true) - $start;
		}
		$this->lastQuery = $query;
		if (mysql_errno()) {
			if (DEVELOPMENT) {
				/*debug(array(
					'code' => mysql_errno(),
					'text' => mysql_error(),
					'query' => $query,
				));*/
			}
			throw new Exception(mysql_errno().': '.mysql_error().
				(DEVELOPMENT ? '<br>Query: '.$this->lastQuery : '')
			, mysql_errno());
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer($profilerKey);
		return $res;
	}

	function fetchAssoc($res) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = mysql_fetch_assoc($res);
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $row;
	}

	function fetchRow($res) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = mysql_fetch_row($res);
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $row;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $res
	 * @param unknown_type $key can be set to NULL to avoid assoc array
	 * @return unknown
	 */
	function fetchAll($res, $key = NULL) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}

		// temp
		if (mysql_errno()) {
			debug(array(mysql_errno() => mysql_error()));
			exit();
		}

		$data = array();
		while (($row = $this->fetchAssoc($res)) !== FALSE) {
			if ($key) {
				$data[$row[$key]] = $row;
			} else {
				$data[] = $row;
			}
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $data;
	}

	function numRows($res) {
		return mysql_num_rows($res);
	}

	function dataSeek($res, $number) {
		return mysql_data_seek($res, $number);
	}

	function lastInsertID() {
		return mysql_insert_id();
	}

	function transaction() {
		$this->perform('BEGIN');
	}

	function commit() {
		$this->perform('COMMIT');
	}

	function escape($string) {
		return mysql_real_escape_string($string);
		// pg_escape_string
	}

	/**
	 * Return ALL rows
	 * @param <type> $table
	 * @param <type> $where
	 * @param <type> $order
	 * @return <type>
	 */
	function fetchSelectQuery($table, $where = array(), $order = '', $addFields = '', $exclusive = false) {
		$res = $this->runSelectQuery($table, $where, $order, $addFields, $exclusive);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runSelectQuery($table, array $where, $order = '', $addFields = '', $exclusive = false) {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
		$res = $qb->runSelectQuery($table, $where, $order, $addFields, $exclusive);
		return $res;
	}

	function runUpdateQuery($table, array $set, array $where) {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
		$res = $qb->runUpdateQuery($table, $set, $where);
		return $res;
	}

	function runInsertQuery($table, array $set) {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
		$res = $qb->runInsertQuery($table, $set);
		return $res;
	}

	function getTableColumns($table) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$table})".$this->getCaller());
		if ($this->numRows($this->perform("SHOW TABLES LIKE '".$this->escape($table)."'"))) {
			$query = "SHOW COLUMNS FROM ".$this->escape($table);
			$res = $this->perform($query);
			$columns = $this->fetchAll($res, 'Field');
		} else {
			$columns = array();
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$table})".$this->getCaller());
		return $columns;
	}

	function __call($method, array $params) {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
		//debug_pre_print_backtrace();
		//debug($method, $params);
		if (method_exists($qb, $method)) {
			return call_user_func_array(array($qb, $method), $params);
		} else {
			throw new Exception($method.' not found in MySQL and SQLBuilder');
		}
	}

}
