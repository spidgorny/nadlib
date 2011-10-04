<?php

class MySQL {
	public $db;
	public $lastQuery;

	function __construct($db = 'f', $host = 'localhost', $login = 'root', $password = '') {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);

		$this->db = $db;
		$c = mysql_pconnect($host, $login, $password);
		mysql_selectdb($this->db);
		mysql_set_charset('utf8');
		//debug(mysql_client_encoding()); exit();
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

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
		$res = mysql_query($query);
		$this->lastQuery = $query;
		if (mysql_errno()) {
			debug(array(
				'code' => mysql_errno(),
				'text' => mysql_error(),
				'query' => $query,
			));
			exit();
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

	function fetchSelectQuery($table, $where = array(), $order = '') {
		$qb = new SQLBuilder();
		$query = $qb->getSelectQuery($table, $where, $order);
		$res = $this->perform($query);
		$data = $this->fetchAll($res);
		return $data;
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

}
