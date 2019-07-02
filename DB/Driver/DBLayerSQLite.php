<?php

/**
 * Class dbLayerSQLite
 * @mixin SQLBuilder
 */
class DBLayerSQLite extends DBLayerBase implements DBInterface
{

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
	var $reserved = [
		'FROM',
	];

	function __construct($file = null)
	{
		$this->file = $file;
		$this->database = basename($this->file);
		$this->connect();
	}

	function connect()
	{
		if (class_exists('SQLite3')) {
			$this->connection = new SQLite3($this->file);
			$this->connection->exec('PRAGMA journal_mode = wal;');
		} else {
			throw new Exception('SQLite3 extension is not enabled');
		}
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return null|SQLite3Result|SQLiteResult
	 * @throws DatabaseException
	 */
	function perform($query, array $params = [])
	{
		if (!$this->connection) {
			debug_pre_print_backtrace();
		}
		$this->lastQuery = $query;
		$profiler = new Profiler();
		$this->lastResult = $this->connection->query($query);
		$this->queryTime += $profiler->elapsed();
		$this->logQuery($query);
		if (!$this->lastResult) {
			debug($this->lastResult, $query, $this->connection->lastErrorMsg());
			throw new DatabaseException($this->connection->lastErrorMsg());
		}
		return $this->lastResult;
	}

	/**
	 * @param SQLiteResult $res
	 * @return int
	 * @throws Exception
	 */
	function numRows($res = NULL)
	{
		$numRows = 0;
		if ($res instanceof SQLite3Result) {
			$res->reset();
			//debug(get_class($res), get_class_methods($res));
			//$all = $this->fetchAll($res);   // will free() inside
			//$numRows = sizeof($all);
			while ($this->fetchAssoc($res) !== FALSE) {
				$numRows++;
			}
			$res->reset();
		} else {
			debug($res);
			throw new DatabaseException('invalid result');
		}
		return $numRows;
	}

	function affectedRows($res = NULL)
	{
		$this->lastResult->numRows();
	}

	function getTables()
	{
		$tables = $this->getTablesEx();
		return array_keys($tables);
	}

	function getTablesEx()
	{
		$this->perform("SELECT *
		FROM sqlite_master
		WHERE type = 'table'
		ORDER BY name
		");
		$tables = $this->fetchAll($this->lastResult, 'name');
		return $tables;
	}

	/**
	 * @param string $table
	 * @return array
	 * @throws Exception
	 */
	function getIndexesFrom($table)
	{
		$this->perform("SELECT * FROM sqlite_master WHERE type = 'index'");
		return $this->fetchAll($this->lastResult);
	}

	function lastInsertID($res = NULL, $table = NULL)
	{
		return $this->connection->lastInsertRowid();
	}

	/**
	 * @param SQLite3Result $res
	 */
	function free($res)
	{
		// The SQLite3Result object has not been correctly initialised
		@$res->finalize();
	}

	function quoteKey($key)
	{
		return '`' . $key . '`';
	}

	function escapeBool($value)
	{
		return intval(!!$value);
	}

	/**
	 * @param string $table
	 * @return array
	 * @throws Exception
	 */
	function getTableColumnsEx($table)
	{
		$res = $this->perform('PRAGMA table_info(' . $this->quoteKey($table) . ')');
		$tableInfo = $this->fetchAll($res, 'name');
//        debug($res, $tableInfo);
		foreach ($tableInfo as &$row) {
			$row['Field'] = $row['name'];
			$row['Type'] = $row['type'];
			$row['Null'] = $row['notnull'] ? 'NO' : 'YES';
		}
		return $tableInfo;
	}

	/**
	 * @param SQLite3Result|string $res_or_query
	 * @param null $index_by_key
	 * @return array
	 * @throws Exception
	 */
	function fetchAll($res_or_query, $index_by_key = NULL)
	{
		if (is_string($res_or_query)) {
			$res = $this->perform($res_or_query);
		} elseif ($res_or_query instanceof SQLSelectQuery) {
			$res = $this->perform($res_or_query.'', $res_or_query->getParameters());
		} elseif ($res_or_query instanceof SQLite3Result) {
			$res = $res_or_query;
		} else {
//			error_log(typ($res_or_query));
			throw new DatabaseException('res is not usable');
		}
//		debug($res_or_query.'');

		$data = [];
		do {
			$row = $res->fetchArray(SQLITE3_ASSOC);
			if ($row) {
				$data[] = $row;
			}
		} while ($row);

		if ($res instanceof SQLite3Result) {
			$res->finalize();
		}

		if ($index_by_key) {
			$data = ArrayPlus::create($data)->IDalize($index_by_key)->getData();
		}
		return $data;
	}

	/**
	 * @param SQLite3Result $res
	 * @return mixed
	 * @throws Exception
	 */
	function fetchAssoc($res)
	{
		if (is_string($res)) {
			$res = $this->perform($res);
		} elseif ($res instanceof SQLSelectQuery) {
			$res = $this->perform($res.'', $res->getParameters());
		} elseif ($res instanceof SQLite3Result) {
//			$res = $res;
		} else {
			debug($res);
			debug_pre_print_backtrace();
			throw new DatabaseException('unknown res');
		}
//		debug($this->lastQuery, typ($res));

		$row = $res->fetchArray(SQLITE3_ASSOC);

		// don't finalize as this may be used somewhere
//		if ($res->numColumns() && $res->columnType(0) != SQLITE3_NULL) {
//			$res->finalize();
//		}
		return $row;
	}

	function escape($str)
	{
		return SQLite3::escapeString($str);
	}

	function transaction()
	{
		return $this->perform('BEGIN');
	}

	function commit()
	{
		return $this->perform('COMMIT');
	}

	function rollback()
	{
		return $this->perform('ROLLBACK');
	}

	public function getScheme()
	{
		return 'sqlite';
	}

}
