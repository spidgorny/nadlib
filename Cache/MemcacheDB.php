<?php

class MemcacheDB implements MemcacheInterface {

	/**
	 * @var DBLayerBase
	 */
	var $db;

	var $table = 'cache';

	function __construct() {
		$this->db = Config::getInstance()->getDB();
	}

	function get($key) {
		$row = $this->getRow($key);
		return $row['value'];
	}

	function getRow($key) {
		$row = $this->db->fetchOneSelectQuery($this->table, [
			'key' => $key,
		]);
		return $row;
	}

	function set($key, $value) {
		$this->db->runUpdateInsert($this->table, [
			'value' => $value,
			'mtime' => new SQLNow(),
		], [
			'key' => $key,
		]);
	}

	function isValid($key = NULL, $expire = 0) {
		$row = $this->getRow($key);
		return (strtotime($row['mtime']) - time()) < $expire;
	}

	function un_set($key) {
		$this->db->runDeleteQuery($this->table, [
			'key' => $key,
		]);
	}

}
