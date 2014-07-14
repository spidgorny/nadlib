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

	/**
	 * @var int - accumulated DB execution time
	 */
	var $dbTime = 0;

	/**
	 * MUST BE UPPERCASE
	 * @var array
	 */
	var $reserved = array(
		'FROM',
	);

	function __construct($file) {
		$this->file = $file;
		$this->connection = new SQLite3($this->file);
	}

	function perform($query) {
		$this->lastQuery = $query;
		$profiler = new Profiler();
		$this->result = $this->connection->query($query);
		$this->dbTime += $profiler->elapsed();
		if (!$this->result) {
			debug($query, $this->connection->lastErrorMsg());
		}
		return $this->result;
	}

	/**
	 * @param $res SQLiteResult
	 * @return mixed
	 */
	function numRows($res = NULL) {
		if ($res instanceof SQLite3Result) {
			//debug(get_class($res), get_class_methods($res));
			//$all = $this->fetchAll($res);   // will free() inside
			//$numRows = sizeof($all);
			$numRows = 0;
			while ($this->fetchAssoc($res) !== FALSE) {
				$numRows++;
			}
			$res->reset();
		} else {
			debug($res);
		}
		return $numRows;
	}

	function affectedRows($res = NULL) {
		$this->result->numRows();
	}

	function getTables() {
		$this->perform("SELECT * FROM dbname.sqlite_master WHERE type='table'");
		return $this->fetchAll($this->result);
	}

	function lastInsertID($res = NULL, $table = NULL) {
		return $this->connection->lastInsertRowid();
	}

	function free($res) {
		$res->finalize();
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

	function transaction() {
		return $this->perform('BEGIN');
	}

	function commit() {
		return $this->perform('COMMIT');
	}

	function rollback() {
		return $this->perform('ROLLBACK');
	}

}
