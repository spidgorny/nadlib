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

	public function setQB(SQLBuilder $qb) {
		$this->qb = $qb;
	}

	function getDSN(array $params) {
		$url = http_build_query($params, NULL, ';', PHP_QUERY_RFC3986);
		$url = str_replace('%20', ' ', $url);	// back convert
		$url = urldecode($url);
		return $url;
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

}
