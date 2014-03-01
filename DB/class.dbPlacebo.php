<?php

/**
 * Class dbPlacebo
 * @mixin SQLBuilder
 */
class dbPlacebo implements DBInterface {

	function perform($query) {
		return '';
	}

	function fetchOptions($a) {
		return '';
	}

	function fetchAll() {
		return array();
	}

	function __call($method, array $params) {
		$qb = Config::getInstance()->qb;
		/** @var $qb SQLBuilder */
		//debug_pre_print_backtrace();
		//debug($method, $params);
		if (method_exists($qb, $method)) {
			return call_user_func_array(array($qb, $method), $params);
		} else {
			throw new Exception($method.' not found in dbPlacebo and SQLBuilder');
		}
	}

	function numRows($res) {
		// TODO: Implement numRows() method.
	}

	function affectedRows() {
		// TODO: Implement affectedRows() method.
	}

	function getTables() {
		// TODO: Implement getTables() method.
	}

	function lastInsertID() {
		// TODO: Implement lastInsertID() method.
	}

	function free($res) {
		// TODO: Implement free() method.
	}

	function quoteKey($key) {
		// TODO: Implement quoteKey() method.
	}

	function escapeBool($value) {
		// TODO: Implement escapeBool() method.
	}
}
