<?php

class dbLayerBase {

	/**
	 * @var SQLBuilder
	 */
	var $qb;

	function setQB() {
		$di = new DIContainer();
		$di->db = $this;
		$qb = new SQLBuilder($di);
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

	function fetchPartition($res, $start, $limit) {
		if ($this->getScheme() == 'mysql') {
			return $this->fetchPartitionMySQL($res, $start, $limit);
		}
		$data = array();
		for ($i = $start; $i < $start + $limit; $i++) {
			$this->dataSeek($i);
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

}
