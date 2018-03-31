<?php

/**
 * Class dbLayerSQLite
 * @mixin SQLBuilder
 */
class DBLayerSQLite extends DBLayerBase implements DBInterface {

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
	var $lastResult;

	/**
	 * MUST BE UPPERCASE
	 * @var array
	 */
	var $reserved = array(
		'FROM',
	);

	function __construct($file) {
		$this->file = $file;
		$this->database = basename($this->file);
		$this->connect();
	}

	function connect() {
		if (class_exists('SQLite3')) {
			$this->connection = new SQLite3($this->file);
		} else {
			throw new Exception('SQLite3 extension is not enabled');
		}
	}

	function perform($query, array $params = []) {
		if (!$this->connection) {
			debug_pre_print_backtrace();
		}
		$this->lastQuery = $query;
		$profiler = new Profiler();
		$this->lastResult = $this->connection->query($query);
		$this->queryTime += $profiler->elapsed();
		if (!$this->lastResult) {
			debug($query, $this->connection->lastErrorMsg());
			throw new Exception('DB query failed');
		}
		return $this->lastResult;
	}

	/**
	 * @param $res SQLiteResult
	 * @return int
	 */
	function numRows($res = NULL) {
		$numRows = 0;
		if ($res instanceof SQLite3Result) {
			//debug(get_class($res), get_class_methods($res));
			//$all = $this->fetchAll($res);   // will free() inside
			//$numRows = sizeof($all);
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
		$this->lastResult->numRows();
	}

	function getTables() {
		$tables = $this->getTablesEx();
		return array_keys($tables);
	}

	function getTablesEx() {
		$this->perform("SELECT *
		FROM sqlite_master
		WHERE type = 'table'
		ORDER BY name
		");
		$tables = $this->fetchAll($this->lastResult, 'name');
		return $tables;
	}

	function getIndexesFrom($table) {
		$this->perform("SELECT * FROM sqlite_master WHERE type = 'index'");
		return $this->fetchAll($this->lastResult);
	}

	function lastInsertID($res = NULL, $table = NULL) {
		return $this->connection->lastInsertRowid();
	}

	/**
	 * @param $res SQLite3Result
	 */
	function free($res) {
		// The SQLite3Result object has not been correctly initialised
		@$res->finalize();
	}

	function quoteKey($key) {
		return '`'.$key.'`';
	}

	function escapeBool($value) {
		return intval(!!$value);
	}

	function getTableColumnsEx($table) {
		$this->perform('PRAGMA table_info('.$this->quoteKey($table).')');
		$tableInfo = $this->fetchAll($this->lastResult, 'name');
		foreach ($tableInfo as &$row) {
			$row['Field'] = $row['name'];
			$row['Type'] = $row['type'];
			$row['Null'] = $row['notnull'] ? 'NO' : 'YES';
		}
		return $tableInfo;
	}

	/**
	 * @param SQLite3Result $res
	 * @return mixed
	 */
	function fetchAssoc($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		if (!is_object($res)) {
			debug($res);
			debug_pre_print_backtrace();
		}
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

	public function getScheme() {
		return 'sqlite';
	}
	
}
