<?php

/**
 * Class dbLayerSQLite
 * @mixin SQLBuilder
 */
class dbLayerSQLite extends dbLayerBase implements DBInterface {

	/**
	 * @var string
	 */
	var $file;

	/**
	 * @var SQLite3
	 */
	var $connection;

	/**
	 * @var string
	 */
	var $lastQuery;

	/**
	 * @var SQLiteResult
	 */
	var $result;

	function __construct($file) {
		$this->file = $file;
		$this->connection = new SQLite3($this->file);
	}

	function perform($query) {
		$this->lastQuery = $query;
		$this->result = $this->connection->query($query);
		if (!$this->result) {
			debug($this->connection->lastErrorMsg());
		}
		return $this->result;
	}

	/**
	 * @param $res SQLiteResult
	 * @return mixed
	 */
	function numRows($res = NULL) {
		if ($res instanceof SQLite3Result) {
			return $res->numRows();
		} else {
			debug($res);
		}
	}

	function affectedRows() {
		$this->result->numRows();
	}

	function getTables() {
		$this->perform("SELECT * FROM dbname.sqlite_master WHERE type='table'");
		return $this->fetchAll($this->result);
	}

	function lastInsertID() {
		return $this->connection->lastInsertRowid();
	}

	function free($res) {
		// nothing
	}

	function quoteKey($key) {
		return '`'.$key.'`';
	}

	function escapeBool($value) {
		return intval(!!$value);
	}

	function getTableColumnsEx($table) {
		$this->perform('PRAGMA table_info('.$this->quoteKey($table).')');
		$tableInfo = $this->fetchAll($this->result, 'name');
		foreach ($tableInfo as &$row) {
			$row['Field'] = $row['name'];
			$row['Type'] = $row['type'];
			$row['Null'] = $row['notnull'] ? 'NO' : 'YES';
		}
		return $tableInfo;
	}

	function fetchAssoc($res) {
		return $res->fetchArray(SQLITE3_ASSOC);
	}

	function escape($str) {
		return SQLite3::escapeString($str);
	}

}
