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

}
