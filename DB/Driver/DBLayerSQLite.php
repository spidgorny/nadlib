<?php

/**
 * Class dbLayerSQLite
 * @mixin SQLBuilder
 * @method  runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  getSelectQuery($table, array $where = [], $order = '', $addSelect = '')
 * @method  runDeleteQuery($table, array $where)
 * @method  runInsertUpdateQuery($table, array $fields, array $where, array $insert = [])
 */
class DBLayerSQLite extends DBLayerBase
{
	/**
	 * @var string
	 */
	public $file;

	/**
	 * @var SQLite3
	 */
	public $connection;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * @var SQLite3Result|false
	 */
	public $lastResult;

	/**
	 * MUST BE UPPERCASE
	 * @var array
	 */
	public $reserved = [
		'FROM',
	];

	public function __construct($file = null)
	{
		$this->file = $file;
		$this->dbName = basename($this->file);
	}

	public function affectedRows($res = null): ?int
	{
		return null;
	}

	/**
	 * @param ?SQLite3Result $res
	 * @throws Exception
	 */
	public function numRows($res = null): int
	{
		$numRows = 0;
		if (!($res instanceof SQLite3Result)) {
			debug($res);
			throw new DatabaseException('invalid result');
		}

		$res->reset();
		//debug(get_class($res), get_class_methods($res));
		//$all = $this->fetchAll($res);   // will free() inside
		//$numRows = sizeof($all);
		while ($this->fetchAssoc($res) !== false) {
			$numRows++;
		}

		$res->reset();

		return $numRows;
	}

	/**
	 * @param SQLite3Result|SQLSelectQuery|string $res
	 * @return mixed
	 * @throws Exception
	 */
	public function fetchAssoc($res)
	{
		if (is_string($res)) {
			$res = $this->perform($res);
		} elseif ($res instanceof SQLSelectQuery) {
			$res = $this->perform($res . '', $res->getParameters());
		}

//		debug($this->lastQuery, typ($res));

		$row = $res->fetchArray(SQLITE3_ASSOC);

		// don't finalize as this may be used somewhere
//		if ($res->numColumns() && $res->columnType(0) != SQLITE3_NULL) {
//			$res->finalize();
//		}
		return $row;
	}

	/**
	 * @param string $query
	 * @return null|SQLite3Result|SQLite3Result
	 * @throws DatabaseException
	 */
	public function perform($query, array $params = [])
	{
		if (!$this->connection) {
//			debug_pre_print_backtrace();
			$this->connect();
		}

		$this->lastQuery = $query;
		$profiler = new Profiler();
		$this->lastResult = $this->connection->query($query);
		$this->queryTime += (float)$profiler->elapsed();
		$this->logQuery($query);
		if (!$this->lastResult) {
			debug($this->lastResult, $query, $this->connection->lastErrorMsg());
			throw new DatabaseException($this->connection->lastErrorMsg());
		}

		return $this->lastResult;
	}

	public function connect(): void
	{
		if (class_exists('SQLite3')) {
			$this->connection = new SQLite3($this->file);
			$this->connection->exec('PRAGMA journal_mode = wal;');
			$this->connection->enableExceptions(true);
		} else {
			throw new Exception('SQLite3 extension is not enabled');
		}
	}

	public function getTables()
	{
		$tables = $this->getTablesEx();
		return array_keys($tables);
	}

	public function getTablesEx()
	{
		$this->perform("SELECT *
		FROM sqlite_master
		WHERE type = 'table'
		ORDER BY name
		");
		return $this->fetchAll($this->lastResult, 'name');
	}

	/**
	 * @param SQLite3Result|string|SQLSelectQuery $res_or_query
	 * @throws Exception
	 */
	public function fetchAll($res_or_query, $index_by_key = null): array
	{
		if (is_string($res_or_query)) {
			$res = $this->perform($res_or_query);
		} elseif ($res_or_query instanceof SQLSelectQuery) {
			$res = $this->perform($res_or_query . '', $res_or_query->getParameters());
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
	 * @param string $table
	 * @return array
	 * @throws Exception
	 */
	public function getIndexesFrom($table)
	{
		$this->perform("SELECT * FROM sqlite_master WHERE type = 'index'");
		return $this->fetchAll($this->lastResult);
	}

	public function lastInsertID($res = null, $table = null): int
	{
		return $this->connection->lastInsertRowid();
	}

	/**
	 * @param SQLite3Result|null $res
	 */
	public function free($res): void
	{
		// The SQLite3Result object has not been correctly initialised
		if ($res instanceof SQLite3Result) {
			@$res->finalize();
		}
	}

	public function escapeBool($value): int
	{
		return intval((bool)$value);
	}

	/**
	 * @param string $table
	 * @return array
	 * @throws Exception
	 */
	public function getTableColumnsEx($table)
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

	public function quoteKey($key): string
	{
		if ($key instanceof AsIs) {
			return $key . '';
		}

		return '`' . $key . '`';
	}

	public function escape($str): string
	{
		return SQLite3::escapeString($str);
	}

	public function transaction()
	{
		return $this->perform('BEGIN');
	}

	public function commit()
	{
		return $this->perform('COMMIT');
	}

	public function rollback()
	{
		return $this->perform('ROLLBACK');
	}

	public function getScheme(): string
	{
		return 'sqlite';
	}

	public function getInfo(): array
	{
		return ['class' => get_class($this)];
	}

	public function getConnection()
	{
		return $this->connection;
	}

	public function getVersion(): void
	{
		// TODO: Implement getVersion() method.
	}

	public function getPlaceholder($field): void
	{
		// TODO: Implement getPlaceholder() method.
	}

	public function __call($name, array $params)
	{
		// TODO: Implement @method  runInsertUpdateQuery($table, array $fields, array $where, array $insert = [])
	}
}
