<?php

class MySQL {
	public $db;
	public $lastQuery;
	public $connection;
	protected static $instance;
	public $queryLog = array();		// set to NULL for disabling

	function __construct($db = NULL, $host = '127.0.0.1', $login = 'root', $password = '') {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db = $db;
		if ($this->db) {
			$this->connect($host, $login, $password);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function connect($host, $login, $password) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		ini_set('mysql.connect_timeout', 1);
		$this->connection = @mysql_pconnect($host, $login, $password);
		if (!$this->connection) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_select_db($this->db, $this->connection);
		if (!$res) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_set_charset('utf8', $this->connection);
		if (!$res) {
			throw new Exception(mysql_error(), mysql_errno());
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

/*
 * Suspicious! ConfigBase takes care of that, isn't it?
 * 	static function getInstance() {
		if (!self::$instance) {
			$config = Config::getInstance();
			self::$instance = new self($config->mysql_db, $config->mysql_host, $config->mysql_login, $config->mysql_password);
			$config->db = self::$instance;
			$config->qb->db = self::$instance;
		}
		return self::$instance;
	}
*/
	function getCaller($stepBack = 2) {
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
		if (isset($GLOBALS['profiler'])) {
			$c = 2;
			do {
				$caller = $this->getCaller($c);
				$c++;
			} while (in_array($caller, array(
				'MySQL::fetchSelectQuery',
				'MySQL::runSelectQuery',
				//'OODBase::findInDB',
				'MySQL::fetchAll',
				//'FlexiTable::findInDB',
				'MySQL::getTableColumns',
			)));
			$profilerKey = __METHOD__." (".$caller.")";
			$GLOBALS['profiler']->startTimer($profilerKey);
		}
		$start = microtime(true);
		$res = @mysql_query($query, $this->connection);
		if (!is_null($this->queryLog)) {
			$diffTime = microtime(true) - $start;
			$this->queryLog[$query] = is_array($this->queryLog[$query]) ? $this->queryLog[$query] : array();
			$this->queryLog[$query]['time'] = ($this->queryLog[$query]['time'] + $diffTime) / 2;
			$this->queryLog[$query]['sumtime'] += $diffTime;
			$this->queryLog[$query]['times']++;
		}
		$this->lastQuery = $query;
		if (mysql_errno($this->connection)) {
			if (DEVELOPMENT) {
				debug(array(
					'code' => mysql_errno($this->connection),
					'text' => mysql_error($this->connection),
					'query' => $query,
				));
			}
			throw new Exception(mysql_errno($this->connection).': '.mysql_error($this->connection).
				(DEVELOPMENT ? '<br>Query: '.$this->lastQuery : '')
			, mysql_errno($this->connection));
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer($profilerKey);
		return $res;
	}

	function fetchAssoc($res) {
		$key = __METHOD__.' ('.$this->lastQuery.')';
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer($key);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		if (is_resource($res)) {
			$row = mysql_fetch_assoc($res);
		} else {
			error_log('is not a resource: '.$this->lastQuery);
			debug('is not a resource', $res);
			debug_pre_print_backtrace();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer($key);
		return $row;
	}

	function fetchRow($res) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = mysql_fetch_row($res);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $row;
	}

	/**
	 * Enter description here...
	 *
	 * @param resource $res
	 * @param string $key can be set to NULL to avoid assoc array
	 * @return array
	 */
	function fetchAll($res, $key = NULL) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}

		// temp
		if (mysql_errno()) {
			debug(array(mysql_errno($this->connection) => mysql_error($this->connection)));
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
		//debug($this->lastQuery, sizeof($data));
		//debug_pre_print_backtrace();
		mysql_free_result($res);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $data;
	}

	function numRows($res) {
		return mysql_num_rows($res);
	}

	function dataSeek($res, $number) {
		return mysql_data_seek($res, $number);
	}

	function lastInsertID() {
		return mysql_insert_id($this->connection);
	}

	function transaction() {
		$this->perform('BEGIN');
	}

	function commit() {
		$this->perform('COMMIT');
	}

	function rollback() {
		$this->perform('ROLLBACK');
	}

	function escape($string) {
		return mysql_real_escape_string($string, $this->connection);
	}

	function quoteSQL($string) {
		return "'".$this->escape($string)."'";
	}

	/**
	 * Return ALL rows
	 * @param <type> $table
	 * @param <type> $where
	 * @param <type> $order
	 * @return <type>
	 */
	function fetchSelectQuery($table, $where = array(), $order = '', $addFields = '', $exclusive = false) {
		// commented to allow working with multiple MySQL objects (SQLBuilder instance contains only one)
		//$res = $this->runSelectQuery($table, $where, $order, $addFields, $exclusive);
		$query = $this->getSelectQuery($table, $where, $order, $addFields, $exclusive);
		$res = $this->perform($query);
		$data = $this->fetchAll($res);
		return $data;
	}

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '', $only = FALSE) {
		$qb = Config::getInstance()->qb;
		$query = $qb->getSelectQuery($table, $where, $order, $selectPlus, $only);
		$res = $this->perform($query);
		$data = $this->fetchAssoc($res);
		return $data;
	}

	function runSelectQuery($table, array $where, $order = '', $selectPlus = '', $only = FALSE) {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuery($table, $where, $order, $selectPlus, $only);
		return $res;
	}

	function runUpdateQuery($table, array $set, array $where) {
		$qb = Config::getInstance()->qb;
		$res = $qb->runUpdateQuery($table, $set, $where);
		return $res;
	}

	function runInsertQuery($table, array $set) {
		$qb = Config::getInstance()->qb;
		$res = $qb->runInsertQuery($table, $set);
		return $res;
	}

	function getTableColumns($table) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$table})".$this->getCaller());
		if ($this->numRows($this->perform("SHOW TABLES LIKE '".$this->escape($table)."'"))) {
			$query = "SHOW COLUMNS FROM ".$this->escape($table);
			$res = $this->perform($query);
			$columns = $this->fetchAll($res, 'Field');
		} else {
			$columns = array();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$table})".$this->getCaller());
		return $columns;
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

	function uncompress($value) {
		return @gzuncompress(substr($value, 4));
	}

	function quoteKey($key) {
		return $key = '`'.$key.'`';
	}

	function switchDB($db) {
		$this->db = $db;
		mysql_select_db($this->db);
	}

}
