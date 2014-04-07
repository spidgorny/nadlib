<?php

/**
 * Class MySQL
 * @mixin SQLBuilder
 */
class MySQL extends dbLayerBase implements DBInterface {

	/**
	 * @var string
	 */
	public $db;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var resource
	 */
	public $lastResult;

	/**
	 * @var resource
	 */
	protected $connection;

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * set to NULL for disabling
	 * @var array
	 */
	public $queryLog = array();

	/**
	 * @var bool Allows logging every query to the error.log.
	 * Helps to detect the reason for white screen problems.
	 */
	public $logToLog = false;

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
		//echo __METHOD__.'<br />';
		//ini_set('mysql.connect_timeout', 3);
		$this->connection = @mysql_pconnect($host, $login, $password);
		if (!$this->connection) {
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_select_db($this->db, $this->connection);
		if (!$res) {
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
			throw new Exception(mysql_error(), mysql_errno());
		}
		$res = mysql_set_charset('utf8', $this->connection);
		if (!$res) {
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
			throw new Exception(mysql_error(), mysql_errno());
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function perform($query) {
		if (isset($GLOBALS['profiler'])) {
			$c = 2;
			$btl = debug_backtrace();
			do {
				$bt = $btl[$c];
				$caller = "{$bt['class']}::{$bt['function']}";
				$c++;
			} while (in_array($caller, array(
				'MySQL::fetchSelectQuery',
				'MySQL::runSelectQuery',
				'OODBase::findInDB',
				'MySQL::fetchAll',
				'FlexiTable::findInDB',
				'MySQL::getTableColumns',
				'MySQL::perform',
				'OODBase::fetchFromDB',
			)));
			$profilerKey = __METHOD__." (".$caller.")";
			$GLOBALS['profiler']->startTimer($profilerKey);
		}
		if ($this->logToLog) {
			$runTime = number_format(microtime(true)-$_SERVER['REQUEST_TIME'], 2);
			error_log($runTime.' '.$query);
		}

		$start = microtime(true);
		$res = $this->lastResult = @mysql_query($query, $this->connection);
		if (!is_null($this->queryLog)) {
			$diffTime = microtime(true) - $start;
			$key = md5($query);
			$this->queryLog[$key] = is_array($this->queryLog[$key]) ? $this->queryLog[$key] : array();
			$this->queryLog[$key]['query'] = $query;
			$this->queryLog[$key]['time'] = ($this->queryLog[$key]['time'] + $diffTime) / 2;
			$this->queryLog[$key]['sumtime'] += $diffTime;
			$this->queryLog[$key]['times']++;
		}
		$this->lastQuery = $query;
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer($profilerKey);
		if (mysql_errno($this->connection)) {
			if (DEVELOPMENT) {
				nodebug(array(
					'code' => mysql_errno($this->connection),
					'text' => mysql_error($this->connection),
					'query' => $query,
				));
			}
			throw new Exception(mysql_errno($this->connection).': '.mysql_error($this->connection).
				(DEVELOPMENT ? '<br>Query: '.$this->lastQuery : '')
			, mysql_errno($this->connection));
		}
		return $res;
	}

	function fetchAssoc($res) {
		$key = __METHOD__.' ('.$this->lastQuery.')';
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer($key);
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		if (is_resource($res)) {
			$row = mysql_fetch_assoc($res);
		} else {
			debug('is not a resource', $this->lastQuery, $res);
			debug_pre_print_backtrace();
			exit();
		}
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer($key);
		return $row;
	}

	function fetchAssocSeek($res) {
		return $this->fetchAssoc($res);
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

	function free($res) {
		if (is_resource($res)) {
			mysql_free_result($res);
		}
	}

	function numRows($res = NULL) {
		if (is_resource($res ?: $this->lastResult)) {
			return mysql_num_rows($res ?: $this->lastResult);
		}
	}

	function dataSeek($res, $number) {
		return mysql_data_seek($res, $number);
	}

	function lastInsertID() {
		return mysql_insert_id($this->connection);
	}

	function transaction() {
		return $this->perform('BEGIN');
	}

	function commit() {
		return $this->perform('COMMIT');
	}

	function rollback() {
		return $this->perform('ROLLBACK');
	}

	function escape($string) {
		return mysql_real_escape_string($string, $this->connection);
	}

	function quoteSQL($string) {
		return "'".$this->escape($string)."'";
	}

	/**
	 * Return ALL rows
	 * @param <type> $order
	 * @param array $where
	 * @param string $order
	 * @param string $addFields
	 * @return array <type>
	 */
	function fetchSelectQuery($table, $where = array(), $order = '', $addFields = '') {
		// commented to allow working with multiple MySQL objects (SQLBuilder instance contains only one)
		//$res = $this->runSelectQuery($table, $where, $order, $addFields);
		$query = $this->getSelectQuery($table, $where, $order, $addFields);
		$res = $this->perform($query);
		$data = $this->fetchAll($res);
		return $data;
	}

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '') {
		$qb = Config::getInstance()->qb;
		$query = $qb->getSelectQuery($table, $where, $order, $selectPlus);
		$res = $this->perform($query);
		$data = $this->fetchAssoc($res);
		return $data;
	}

	function runSelectQuery($table, array $where, $order = '', $selectPlus = '') {
		$qb = Config::getInstance()->qb;
		$res = $qb->runSelectQuery($table, $where, $order, $selectPlus);
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

	function getDatabaseCharacterSet() {
		return current($this->fetchAssoc('show variables like "character_set_database"'));
	}

	/**
	 * @return string[]
	 */
	function getTables() {
		$list = $this->fetchAll('SHOW TABLES');
		foreach ($list as &$row) {
			$row = current($row);
		}
		return $list;
	}

	function getTableCharset($table) {
		$query = "SELECT CCSA.* FROM information_schema.`TABLES` T,
    	information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA
		WHERE CCSA.collation_name = T.table_collation
		/*AND T.table_schema = 'schemaname'*/
		AND T.table_name = '".$table."'";
		$row = $this->fetchAssoc($query);
		return $row;
	}

	/**
	 * Return a 2D array
	 * @param $table
	 * @return array
	 */
	function getTableColumnsEx($table) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$table})".Debug::getCaller());
		if ($this->numRows($this->perform("SHOW TABLES LIKE '".$this->escape($table)."'"))) {
			$query = "SHOW FULL COLUMNS FROM ".$this->quoteKey($table);
			$res = $this->perform($query);
			$columns = $this->fetchAll($res, 'Field');
		} else {
			$columns = array();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$table})".Debug::getCaller());
		return $columns;
	}

	function __call($method, array $params) {
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.'() not found in '.get_class($this).' and SQLBuilder');
		}
	}

	function quoteKey($key) {
		return $key = '`'.$key.'`';
	}

	function switchDB($db) {
		$this->db = $db;
		mysql_select_db($this->db);
	}

	function fetchOptions($query) {
		$data = array();
		if (is_string($query)) {
			$result = $this->perform($query);
		} else {
			$result = $query;
		}
		while (($row = mysql_fetch_row($result)) != FALSE) {
			list($key, $val) = $row;
			$data[$key] = $val;
		}
		return $data;
	}

	function affectedRows() {
		return mysql_affected_rows();
	}

	function getIndexesFrom($table) {
		return $this->fetchAll('SHOW INDEXES FROM '.$table, 'Key_name');
	}

	function escapeBool($value) {
		return intval(!!$value);
	}

	function getScheme() {
		return get_class($this);
	}

}
