<?php

/**
 * Class DBLayerBase
 * @mixin SQLBuilder
 */
class DBLayerBase implements DBInterface {

	/**
	 * @var SQLBuilder
	 */
	var $qb;

	/**
	 * List of reserved words for each DB
	 * which can't be used as field names and must be quoted
	 * @var array
	 */
	protected $reserved = array();

	/**
	 * @var resource
	 */
	var $connection;

	/**
	 * @var string
	 */
	var $lastQuery;

	/**
	 * @var int
	 */
	var $queryCount = 0;

	/**
	 * @var int Time in seconds
	 */
	var $queryTime = 0;

	/**
	 * set to NULL for disabling
	 * @var QueryLog
	 */
	protected $queryLog;

	/**
	 * @var bool Allows logging every query to the error.log.
	 * Helps to detect the reason for white screen problems.
	 */
	public $logToLog = false;

	/**
	 * @var string DB name (file name)
	 */
	public $database;

	function setQB(SQLBuilder $qb = NULL) {
		$this->qb = $qb;
	}

	/**
	 * @return string 'mysql', 'pg', 'ms'... PDO will override this
	 */
	function getScheme() {
		return strtolower(str_replace('DBLayer', '', get_class($this)));
	}

	function __call($method, array $params) {
		if (!$this->qb) {
			if (!$this->qb) {
				throw new DatabaseException(__CLASS__ . ' has no QB');
			}
		}
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_class($this).' and SQLBuilder');
		}
	}

	function dataSeek($res, $i) {
	}

	function fetchAssocSeek($res) {
		return NULL;
	}

	function fetchPartition($res, $start, $limit) {
		if ($this->getScheme() == 'mysql') {
			return $this->fetchPartitionMySQL($res, $start, $limit);
		}
		$max = $start + $limit;
		$max = min($max, $this->numRows($res));
		$data = array();
		for ($i = $start; $i < $max; $i++) {
			$this->dataSeek($res, $i);
			$row = $this->fetchAssocSeek($res);
			if ($row !== false) {
				$data[] = $row;
			} else {
				break;
			}
		}

		// never free as one may retrieve another portion
		//$this->free($res);
		return $data;
	}

	function saveQueryLog($query, $time) {
		$this->queryCount++;
		$this->queryTime += $time;
	}

	function getReserved() {
		return $this->reserved;
	}

	function perform($query, array $params = []) {
		return NULL;
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

	function numRows($res = NULL) {
		return 0;
	}

	function affectedRows($res = NULL) {
		return 0;
	}

	function getTables() {
		return array();
	}

	function lastInsertID($res, $table = NULL) {
		return 0;
	}

	function free($res) {
		// TODO: Implement free() method.
	}

	function quoteKey($key) {
		$reserved = $this->getReserved();
		if (in_array(strtoupper($key), $reserved)) {
			$key = $this->db->quoteKey($key);
		}
		return $key;
	}

	function escape($string) {
		throw new Exception('Implement '.__METHOD__);
	}

	function escapeBool($value) {
		return $value;
	}

	function fetchAssoc($res) {
		return array();
	}

	function getTablesEx() {
		return array();
	}

	function getTableColumnsEx($table) {
		return array();
	}

	function getIndexesFrom($table) {
		return array();
	}

	function isConnected() {
		return !!$this->connection;
	}

	function getTableColumns($table) {

	}

	function getQueryLog() {
		if (!$this->queryLog) {
			$this->queryLog = new QueryLog();
		}
		return $this->queryLog;
	}

	function isMySQL() {
		return in_array(
			$this->getScheme(),
			array('mysql', 'mysqli'));
	}

	function isPostgres() {
		return $this->getScheme() == 'psql';
	}

	function isSQLite() {
		return $this->getScheme() == 'sqlite';
	}

	function clearQueryLog() {
		$this->queryLog = NULL;
	}

	function fetchAll($res_or_query, $index_by_key = NULL) {
		// TODO: Implement fetchAll() method.
	}

	function quoteKeys(array $a) {
		$c = array();
		foreach ($a as $b) {
			$c[] = $this->quoteKey($b);
		}
		return $c;
	}

}