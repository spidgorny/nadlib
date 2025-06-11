<?php

class MemcacheDB implements MemcacheInterface
{

	/**
	 * @var DBInterface
	 */
	public $db;

	public $table = 'cache';

	public function __construct()
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function get($key)
	{
		$row = $this->getRow($key);
		return $row['value'];
	}

	public function getRow($key)
	{
		return $this->db->fetchOneSelectQuery($this->table, [
			'key' => $key,
		]);
	}

	public function set($key, $value): void
	{
		$this->db->runUpdateInsert($this->table, [
			'value' => $value,
			'mtime' => new SQLNow(),
		], [
			'key' => $key,
		]);
	}

	public function isValid($key = null, $expire = 0): bool
	{
		$row = $this->getRow($key);
		return (strtotime($row['mtime']) - time()) < $expire;
	}

	public function un_set($key): void
	{
		$this->db->runDeleteQuery($this->table, [
			'key' => $key,
		]);
	}

}
