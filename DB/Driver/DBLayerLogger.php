<?php

/**
 * Class DBLayerLogger
 * @mixin SQLBuilder
 */
class DBLayerLogger extends DBLayerBase implements DBInterface {

	var $logger;

	var $data = [];

	function __construct() {
		// recursion:
		//$this->qb = Config::getInstance()->getQb();
	}

	function log($query) {
		if ($this->logger) {
			$this->logger->log($query);
		} else {
			echo '>> ', $query, BR;
		}
	}

	function perform($query, array $params = []) {
		$this->log($query);
		return '';
	}

	function fetchOptions($a) {
		$this->log(__METHOD__);
		return '';
	}

	function fetchAll($res_or_query, $index_by_key = NULL) {
		$this->log(__METHOD__);
		return array();
	}

	function __call($method, array $params) {
		if (!$this->qb) {
			throw new Exception(get_called_class().' does not have QB');
		}
		if (method_exists($this->qb, $method)) {
			$this->log($method);
			return call_user_func_array(array($this->qb, $method), $params);
		} else {
			throw new Exception($method.' not found in '.get_called_class().' and SQLBuilder');
		}
	}

	function numRows($res = NULL) {
		$this->log(__METHOD__);
	}

	function affectedRows($res = NULL) {
		$this->log(__METHOD__);
	}

	function getTables() {
		$this->log(__METHOD__);
	}

	function lastInsertID($res = NULL, $table = NULL) {
		$id = $this->data['id'];
		$this->log(__METHOD__.' id: '.$id);
		return $id;
	}

	function free($res) {
		$this->log(__METHOD__);
	}

	function quoteKey($key) {
		$this->log(__METHOD__);
	}

	function escape($string) {
		return $string;
	}

	function escapeBool($value) {
		$this->log(__METHOD__);
	}

	function fetchAssoc($res) {
		$this->log(__METHOD__);
	}

	function transaction() {
		$this->log(__METHOD__);
	}

	function commit() {
		$this->log(__METHOD__);
	}

	function rollback() {
		$this->log(__METHOD__);
	}

	public function getScheme() {
		$this->log(__METHOD__);
	}

	function getTablesEx() {
		$this->log(__METHOD__);
	}

	function getTableColumnsEx($table) {
		$this->log(__METHOD__);
	}

	function getIndexesFrom($table) {
		$this->log(__METHOD__);
	}

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '') {
		$this->log(__METHOD__);
		return $this->data;
	}

	function getInsertQuery($table, array $columns) {
		$this->log(__METHOD__);
		$this->data = $columns;
		if (!ifsetor($this->data['id'])) {
			$id = rand(1, 100);
			$this->data['id'] = $id;
			debug($this->data);
		}
		return $this->qb->getInsertQuery($table, $columns);
	}

}
