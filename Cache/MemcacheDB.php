<?php

class MemcacheDB implements MemcacheInterface {

	/**
	 * @var dbLayerBase
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
		$row = $this->db->fetchOneSelectQuery($this->table, array(
			'key' => $key,
		));
		return $row;
	}

	function set($key, $value) {
		$this->db->runUpdateInsert($this->table, array(
			'value' => $value,
			'mtime' => new SQLNow(),
		), array(
			'key' => $key,
		));
	}

	function isValid($key = NULL, $expire = 0) {
		$row = $this->getRow($key);
		return (strtotime($row['mtime']) - time()) < $expire;
	}

	function un_set($key) {
		$this->db->runDeleteQuery($this->table, array(
			'key' => $key,
		));
	}

}
