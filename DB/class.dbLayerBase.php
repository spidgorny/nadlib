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

	function __call($method, array $params) {
		if (method_exists($this->qb, $method)) {
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_class($this).' and SQLBuilder');
		}
	}

}
