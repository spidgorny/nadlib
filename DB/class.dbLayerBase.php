<?php

/**
 * Class dbLayerBase
 * @mixin SQLBuilder
 */
class dbLayerBase implements DBInterface {

	/**
	 * @var SQLBuilder
	 */
	var $qb;

	/**
	 * List of reserved words for each DB
	 * which can't be used as field names and must be quoted
	 * @var array
	 */
	var $reserved = array();

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
	 * @var array
	 */
	public $queryLog = array();

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
		$this->qb = $qb ?: Config::getInstance()->getQb();
	}

	function getDSN(array $params) {
		$url = http_build_query($params, NULL, ';', PHP_QUERY_RFC3986);
		$url = str_replace('%20', ' ', $url);	// back convert
		$url = urldecode($url);
		return $url;
	}

	/**
	 * @return string 'mysql', 'pg', 'ms'... PDO will override this
	 */
	function getScheme() {
		return strtolower(str_replace('dbLayer', '', get_class($this)));
	}

	function __call($method, array $params) {
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_class($this).' and SQLBuilder');
		}
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
		$this->free($res);
		return $data;
	}

	function saveQueryLog($query, $time) {
		$this->queryCount++;
		$this->queryTime += $time;
	}

	function getReserved() {
		return $this->reserved;
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

	function perform($query) {
		return NULL;
	}

	function numRows($res = NULL) {
		return NULL;
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
	}

	function quoteKey($key) {
		return $key;
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

}
