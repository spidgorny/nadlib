<?php

/**
 * Class dbLayerBase
 * @mixin SQLBuilder
 */
class dbLayerBase {

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
	 * @var int - accumulated DB execution time
	 */
	var $dbTime = 0;

	function setQB(SQLBuilder $qb = NULL) {
		$di = new DIContainer();
		$di->db = $this;
		$qb = $qb ?: new SQLBuilder($di);
		$this->qb = $qb;
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

}
