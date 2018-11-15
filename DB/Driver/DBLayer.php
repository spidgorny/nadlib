<?php

/**
 * Class dbLayer
 * @mixin SQLBuilder
 */
class DBLayer extends DBLayerBase implements DBInterface
{

	/**
	 * @var resource
	 */
	public $connection = null;

	var $LAST_PERFORM_RESULT;

	/**
	 * todo: use setter & getter method
	 *
	 * contains query builder class used as mixin.
	 *
	 * @var null
	 */
	public $qb = null;

	var $AFFECTED_ROWS = NULL;

	/**
	 * @var MemcacheArray
	 */
	protected $mcaTableColumns;

	/**
	 * @var string
	 */
	public $lastQuery;

	/**
	 * Transaction count because three are no nested transactions
	 * @var int
	 */
	protected $inTransaction = 0;

	/**
	 * @var string DB name
	 */
	var $db;

	var $reserved = array(
		'SELECT', 'LIKE', 'TO',
	);

	protected $dbName;

	protected $user;

	protected $pass;

	protected $host;

	protected $lastBacktrace;

	/**
	 * @param string $dbName
	 * @param string $user
	 * @param string $pass
	 * @param string $host
	 * @throws Exception
	 */
	function __construct($dbName = NULL, $user = NULL, $pass = NULL, $host = "localhost")
	{
//		debug_pre_print_backtrace();
		$this->dbName = $dbName;
//		pre_print_r($this->dbName);
		$this->user = $user;
		$this->pass = $pass;
		$this->host = $host;
		if ($dbName) {
			$this->connect($dbName, $user, $pass, $host);
			//debug(pg_version()); exit();

			if ($this->getVersion() >= 8.4) {
				$query = "select * from pg_get_keywords() WHERE catcode IN ('R', 'T')";
				$words = $this->fetchAll($query, 'word');
				$this->reserved = array_keys($words);
				$this->reserved = array_map('strtoupper', $this->reserved); // important
			}
		}
		if (DEVELOPMENT) {
			$this->queryLog = new QueryLog();
		}
	}

	function getVersion()
	{
		$version = pg_version();
		return $version['server'];
	}

	/**
	 * @return bool
	 */
	function isConnected()
	{
		return !!$this->connection
			&& pg_connection_status($this->connection) === PGSQL_CONNECTION_OK;
	}

	function getConnection()
	{
		return $this->connection;
	}

	function reconnect()
	{
		$this->connect($this->dbName, $this->user, $this->pass, $this->host);
	}

	function connect($dbName, $user, $pass, $host = "localhost")
	{
		$this->database = $dbName;
		$string = "host=$host dbname=$dbName user=$user password=$pass";
		#debug($string);
		#debug_print_backtrace();
		$this->connection = pg_connect($string);
		if (!$this->connection) {
			throw new Exception("No PostgreSQL connection to $host.");
			//printbr('Error: '.pg_errormessage());	// Warning: pg_errormessage(): No PostgreSQL link opened yet
		} else {
			$this->perform("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;");
		}
		//print(pg_client_encoding($this->connection));
		return true;
	}

	function perform($query, array $params = array())
	{
//		echo $query, BR;
		$prof = new Profiler();

		if (false === $this->LAST_PERFORM_RESULT) {
			$this->lastBacktrace = array_map(function ($el) {
				unset($el['object']);
				unset($el['args']);
				return $el;
			}, $this->lastBacktrace);
			debug($this->lastBacktrace);
			die(pg_errormessage($this->connection));
			throw new DatabaseException('Last query has failed.' . PHP_EOL . $this->lastQuery . PHP_EOL . pg_errormessage($this->connection));
		}

		$this->lastQuery = $query;
		if (!is_resource($this->connection)) {
			debug('no connection', $this->connection, $query . '');
			throw new DatabaseException('No connection');
		}

		if ($query instanceof SQLSelectQuery) {
			$params = $query->getParameters();
			$query = $query->__toString();
//			debug($query, $params);
		}

		try {
			if ($params) {
				pg_prepare($this->connection, '', $query);
				$this->LAST_PERFORM_RESULT = pg_execute($this->connection, '', $params);
			} else {
				$this->LAST_PERFORM_RESULT = @pg_query($this->connection, $query);
			}
			$this->queryTime = $prof->elapsed();
		} catch (Exception $e) {
			//debug($e->getMessage(), $query);
			$errorMessage = is_resource($this->LAST_PERFORM_RESULT)
				? pg_result_error($this->LAST_PERFORM_RESULT)
				: '';
			$e = new DatabaseException(
				'[' . $e->getCode() . '] ' . $e->getMessage() . BR .
				//pg_errormessage($this->connection).BR.
				'Error' . $errorMessage . BR .
				$query, $e->getCode());
			$e->setQuery($query);
			throw $e;
		}

		if (!$this->LAST_PERFORM_RESULT) {
			//debug_pre_print_backtrace();
			//debug($query);
			//debug($this->queryLog->queryLog);
			$e = new DatabaseException(
				pg_errormessage($this->connection));
			$e->setQuery($query);
			throw $e;
		}

		$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
		if ($this->queryLog) {
			$this->queryLog->log($query, $prof->elapsed(), $this->AFFECTED_ROWS, $this->LAST_PERFORM_RESULT);
		}

		$this->logQuery($query);	// uses $this->queryTime

		$this->lastQuery = $query;
		$this->queryCount++;
		$this->lastBacktrace = debug_backtrace();
		return $this->LAST_PERFORM_RESULT;
	}

	function performWithParams($query, $params)
	{
		$prof = new Profiler();
		$this->lastQuery = $query;
		$this->LAST_PERFORM_RESULT = pg_query_params($this->connection, $query, $params);
		if (!$this->LAST_PERFORM_RESULT) {
			debug($query);
			debug_pre_print_backtrace();
			throw new Exception(pg_errormessage($this->connection) . BR . $query);
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->queryLog) {
				$this->queryLog->log($query, $prof->elapsed(), $this->AFFECTED_ROWS);
			}
		}
		$this->queryCount++;
		return $this->LAST_PERFORM_RESULT;
	}

	/**
	 * Return one dimensional array
	 * @param $table
	 * @return array
	 */
	function getTableColumns($table)
	{
		if (!$table) {
			debug_pre_print_backtrace();
		}
		$meta = pg_meta_data($this->connection, $table);
		if (is_array($meta)) {
			return array_keys($meta);
		} else {
			error("Table not found: <strong>$table</strong>");
			exit();
		}
	}

	function getTableColumnsEx($table)
	{
		$meta = pg_meta_data($this->connection, $table);
		return $meta;
	}

	function getTableColumnsCached($table)
	{
		//debug($table); exit;
		TaylorProfiler::start(__METHOD__);
		if (!$this->mcaTableColumns) {
			$this->mcaTableColumns = new MemcacheArray(__CLASS__ . '.' . __FUNCTION__, 24 * 60 * 60);
		}
		$cache =& $this->mcaTableColumns->data;
		//debug($cache); exit;

		if (!$cache[$table]) {
			$meta = pg_meta_data($this->connection, $table);
			if (is_array($meta)) {
				$cache[$table] = array_keys($meta);
			} else {
				error("Table not found: <strong>$table</strong>");
				exit();
			}
		}
		$return = $cache[$table];
		TaylorProfiler::stop(__METHOD__);
		// used to only attach columns in bug list
		$pageAttachCustom = array('BugLog', 'Filter');
		if (in_array($_REQUEST['pageType'], $pageAttachCustom)) {
			$cO = CustomCatList::getInstance($_SESSION['sesProject']);
			if (is_array($cO->customColumns)) {
				foreach ($cO->customColumns AS $cname) {
					$return[] = $cname;
				}
			}
		}
		return $return;
	}

	function getColumnTypes($table)
	{
		$meta = pg_meta_data($this->connection, $table);
		if (is_array($meta)) {
			$return = array();
			foreach ($meta as $col => $m) {
				$return[$col] = $m['type'];
			}
			return $return;
		} else {
			error("Table not found: <strong>$table</strong>");
			exit();
		}
	}

	function getTableDataEx($table, $where = "", $what = "*")
	{
		$query = "select " . $what . " from $table";
		if (!empty($where)) {
			$query .= " where $where";
		}
		$result = $this->fetchAll($query);
		return $result;
	}

	/**
	 * @param $table
	 * @param $column
	 * @param string $where
	 * @param null $order
	 * @param string $key
	 * @return array
	 * @throws Exception
	 * @throws MustBeStringException
	 * @deprecated
	 * @see SQLBuilder::getTableOptions
	 *
	 * function getTableOptions($table, $column, $where = "", $order = NULL, $key = 'id') {
	 * $tableName = $this->getFirstWord($table);
	 * if (is_array($where) && $where) {
	 * $where = $this->quoteWhere($where);
	 * $where = implode(' AND ', $where);
	 * } elseif (!$where) {
	 * $where = '1 = 1';
	 * }
	 * if ($order) {
	 * $where .= ' '.$order;
	 * }
	 * $a = $this->getTableDataEx($table, $where, $tableName.'.*, '.$column);
	 *
	 * // select login.*, coalesce(name, '') || ' ' || coalesce(surname, '') AS combined from login where relcompany = '47493'
	 * $as = trimExplode(' AS ', $column);
	 * if ($as[1]) {
	 * $column = $as[1];
	 * }
	 *
	 * $b = array();
	 * foreach ($a as $row) {
	 * $b[$row[$key]] = $row[$column];
	 * }
	 * return $b;
	 * }
	 * /**/

	/**
	 * fetchAll() equivalent with $key and $val properties
	 * @param $query
	 * @param null $key
	 * @param null $val
	 * @return array
	 */
	function getTableDataSql($query, $key = NULL, $val = NULL)
	{
		if (is_string($query)) {
			$result = $this->perform($query);
		} else {
			$result = $query;
		}
		$return = array();
		while ($row = pg_fetch_assoc($result)) {
			if ($val) {
				$value = $row[$val];
			} else {
				$value = $row;
			}

			if ($key) {
				$return[$row[$key]] = $value;
			} else {
				$return[] = $value;
			}
		}
		pg_free_result($result);
		return $return;
	}

	/**
	 * Returns a list of tables in the current database
	 * @return string[]
	 * @throws DatabaseException
	 */
	function getTables()
	{
		$query = "select relname
		from pg_class
		where not relname ~ 'pg_.*'
		and not relname ~ 'sql_.*'
		and relkind = 'r'
		ORDER BY relname";
		$result = $this->perform($query);
		$return = pg_fetch_all($result);
		pg_free_result($result);
		return ArrayPlus::create($return)->column('relname')->getData();
	}

	/**
	 * Returns a list of tables in the current database
	 * @return string[]
	 * @throws DatabaseException
	 */
	function getViews()
	{
		$query = "select relname
		from pg_class
		where not relname ~ 'pg_.*'
		and not relname ~ 'sql_.*'
		and relkind = 'v'
		ORDER BY relname";
		$result = $this->perform($query);
		$return = pg_fetch_all($result);
		pg_free_result($result);
		return ArrayPlus::create($return)->column('relname')->getData();
	}

	function describeView($viewName)
	{
		return first(
			$this->fetchAssoc(
				$this->perform("select pg_get_viewdef($1, true)", array(
					$viewName
				))
			)
		);
	}

	function getColumnDefault($table)
	{
		$query = "SELECT *
		FROM information_schema.columns
		where table_name = '" . $table . "'
		ORDER BY ordinal_position";
		$data = $this->fetchAll($query);
		foreach ($data as &$row) {
			if (contains($row['column_default'], 'nextval')) {
				$parts = trimExplode("'", $row['column_default']);
				if (sizeof($parts) >= 2) {
					$row['sequence'] = $parts[1];
					$row['sequence'] = str_replace('"', '', $row['sequence']);  // can be quoted
				}
			}
		}
		return ArrayPlus::create($data)->IDalize('column_name')->getData();
	}

	function dataSeek($res, $number)
	{
		return pg_result_seek($res, $number);
	}

	function transaction($serializable = false)
	{
		if ($this->inTransaction) {
			//error('BEGIN inTransaction: '.$this->inTransaction.'+1');
			$this->inTransaction++;
			return true;
		}
		$this->inTransaction++;
		if ($serializable) {
			$this->perform('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
		}
//		print('[[BEGIN]]'.BR);
		return $this->perform("BEGIN");
	}

	function commit()
	{
		$this->inTransaction--;
		if ($this->inTransaction) {
			//error('COMMIT inTransaction: '.$this->inTransaction);
			//debug_pre_print_backtrace();
			return true;
		}
//		print('[[COMMIT]]'.BR);
		//debug_pre_print_backtrace();
		return $this->perform("commit");
	}

	function rollback()
	{
		$this->inTransaction--;
		if ($this->inTransaction) {
			//error('ROLLBACK inTransaction: '.$this->inTransaction);
			return true;
		}
//		print('[[ROLLBACK]]'.BR);
		return $this->perform("rollback");
	}

	/**
	 * @param $value
	 * @param null $key
	 * @return string
	 * @throws MustBeStringException
	 */
	function quoteSQL($value, $key = NULL)
	{
		if ($value === NULL) {
			return "NULL";
		} else if ($value === FALSE) {
			return "'f'";
		} else if ($value === TRUE) {
			return "'t'";
		} else if (is_int($value)) {    // is_numeric - bad: operator does not exist: character varying = integer
			return $value;
		} else if (is_bool($value)) {
			return $value ? "'t'" : "'f'";
		} else if ($value instanceof SQLParam) {
			return $value;
		} elseif (is_scalar($value)) {
			return "'" . $this->escape($value) . "'";
		} else {
			debug($key, $value);
			throw new MustBeStringException('Must be string.');
		}
	}

	/**
	 * Overrides because of pg_fetch_all
	 * @param resource|string $result
	 * @param null $key
	 * @return array
	 * @throws Exception
	 */
	function fetchAll($result, $key = NULL)
	{
		$params = [];
		if ($result instanceof SQLSelectQuery) {
			/** @var SQLSelectQuery $queryObj */
			$queryObj = $result;
			$result = $queryObj->getQuery();
			$params = $queryObj->getParameters();
		}
		if (is_string($result)) {
			//debug($result);
			$result = $this->perform($result, $params);
		}
		//debug($this->numRows($result));
		$res = pg_fetch_all($result);
		pg_free_result($result);
		if (ifsetor($_REQUEST['d']) == 'q') {
			debug($this->lastQuery, sizeof($res));
		}
		if (!$res) {
			$res = array();
		} elseif ($key) {
			$ap = ArrayPlus::create($res)->IDalize($key)->getData();
			//debug(sizeof($res), sizeof($ap));
			$res = $ap;
		}

		return $res;
	}

	/**
	 * @param resource/query $result
	 * @return array
	 * @throws DatabaseException
	 */
	function fetchAssoc($res)
	{
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = pg_fetch_assoc($res);
		/*      // problem in OODBase
		 * 		if (!$row) {
					$row = array();
				}*/
		return $row;
	}

	/**
	 * Called after dataSeek()
	 * @param $res
	 * @return array
	 */
	function fetchAssocSeek($res)
	{
		return $this->fetchAssoc($res);
	}

	function getAllRows($query)
	{
		$result = $this->perform($query);
		$data = $this->fetchAll($result);
		return $data;
	}

	function getFirstRow($query)
	{
		$result = $this->perform($query);
		$row = pg_fetch_assoc($result);
		return $row;
	}

	function getFirstValue($query)
	{
		$result = $this->perform($query);
		$row = pg_fetch_row($result);
		$value = $row[0];
		return $value;
	}

	function numRows($query = NULL)
	{
		if (is_string($query)) {
			$query = $this->perform($query);
		}
		return pg_num_rows($query);
	}

	function getLastInsertID($res = NULL, $table = 'not required since 8.1')
	{
		$pgv = pg_version();
		if ($pgv['server'] >= 8.1) {
			$id = $this->lastval();
		} else {
			$oid = pg_last_oid($res);
			$id = $this->sqlFind('id', $table, "oid = '" . $oid . "'");
		}
		return $id;
	}

	/**
	 * Compatibility.
	 * @param $res
	 * @param $table - optional
	 * @return null
	 */
	function lastInsertID($res, $table = NULL)
	{
		return $this->getLastInsertID($res, $table);
	}

	protected function lastval()
	{
		$res = $this->perform('SELECT LASTVAL() AS lastval');
		$row = $this->fetchAssoc($res);
		$lv = $row['lastval'];
		return $lv;
	}

	function getComment($table, $column)
	{
		$query = 'select
	 a.attname  as "colname"
	,a.attrelid as "tableoid"
	,a.attnum   as "columnoid"
	,col_description(a.attrelid, a.attnum) as "comment"
from
	pg_catalog.pg_attribute a
	inner join pg_catalog.pg_class c on a.attrelid = c.oid
where
		c.relname = ' . $this->quoteSQL($table) . '
	and a.attnum > 0
	and a.attisdropped is false
	and pg_catalog.pg_table_is_visible(c.oid)
order by a.attnum';
		$rows = $this->fetchAll($query);
		$assoc = ArrayPlus::create($rows)->column_assoc('colname', 'comment')->getData();
		$comment = $assoc[$column];
		//debug($query, $rows, $assoc, $comment);
		return $comment;
	}

	/**
	 * Uses find_in_set function which is not built-in
	 * @see SQLBuilder::array_intersect()
	 *
	 * @param array $options
	 * @param string $field
	 * @return string
	 */
	function getArrayIntersect(array $options, $field = 'list_next')
	{
		$bigOR = array();
		foreach ($options as $n) {
			$bigOR[] = "FIND_IN_SET('" . $n . "', {$field})";
		}
		$bigOR = "(" . implode(' OR ', $bigOR) . ")";
		return $bigOR;
	}

	function escape($str)
	{
		return pg_escape_string($str);
	}

	/**
	 * @param $method
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	function __call($method, array $params)
	{
		if (method_exists($this->getQb(), $method)) {
			return call_user_func_array(array($this->getQb(), $method), $params);
		} else {
			throw new Exception('Method ' . __CLASS__ . '::' . $method . ' doesn\'t exist.');
		}
	}

	/**
	 * Will quote simple key names.
	 * If the key contains special chars,
	 * it thinks it's a function call like trim(field)
	 * and quoting is not done.
	 */
	function quoteKey($key)
	{
		if (ctype_alpha($key)) {
			$isFunc = function_exists('pg_escape_identifier');
			if ($isFunc && $this->isConnected()) {
				$key = pg_escape_identifier($key);
			} else {
				$key = '"' . $key . '"';
			}
		} // else it can be functions (of something)
		return $key;
	}

	function getCallerFunction()
	{
		$skipFunctions = array(
			'runSelectQuery',
			'fetchSelectQuery',
			'sqlFind',
			'getAllRows',
			'perform',
			'fetchFromDB',
			'findInDB',
			'retrieveData',
			'init',
			'__construct',
			'getInstance',
		);
		$debug = debug_backtrace();
		$prev = array_shift($debug);    // getCallerFunction
		while (sizeof($debug) && in_array($debug[0]['function'], $skipFunctions)) {
			$prev = array_shift($debug);
		}
		reset($debug);
		$content = array();
		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach (range(1, 2) as $_) {
			$func = current($debug);
			$func['line'] = $prev['line'];    // line is from the parent function?
			$content[] = $func['class'] . '::' . $func['function'] . '#' . $func['line'];
			next($debug);
		}
		$content = implode(' < ', $content);
		return $content;
	}

	/**
	 * http://www.postgresql.org/docs/9.3/static/datatype-money.html
	 * @param string $source
	 * @return float
	 */
	function getMoney($source = '$1,234.56')
	{
		$source = str_replace('$', '', $source);
		$source = str_replace(',', '', $source);
		$source = floatval($source);
		return $source;
	}

	/**
	 * @param $table
	 * @return array
	 * @throws Exception
	 */
	function getIndexesFrom($table)
	{
		return $this->fetchAll('select *, pg_get_indexdef(indexrelid)
		from pg_index
		where indrelid = \'' . $table . '\'::regclass');
	}

	function free($res)
	{
		if (is_resource($res)) {
			pg_free_result($res);
		}
	}

	function escapeBool($value)
	{
		return $value ? 'true' : 'false';
	}

	public function setQb(SQLBuilder $qb = NULL)
	{
		$this->qb = $qb;
	}

	public function getQb()
	{
		if (!isset($this->qb)) {
			$db = Config::getInstance()->getDB();
			$this->setQb(new SQLBuilder($db));
		}

		return $this->qb;
	}

	function affectedRows($res = NULL)
	{
		return pg_affected_rows($res);
	}

	public function getScheme()
	{
		return 'postgresql';
	}

	function getResultFields($res)
	{
		$fields = array();
		for ($f = 0; $f < pg_num_fields($res); $f++) {
			$newField = pg_fieldname($res, $f);
			$fields[$newField] = pg_field_type($res, $f);
		};
		return $fields;
	}

	function getForeignKeys($table)
	{
		return $this->fetchAll(
			"SELECT
	tc.constraint_name, tc.table_name, kcu.column_name, 
	ccu.table_name AS foreign_table_name,
	ccu.column_name AS foreign_column_name,
	constraint_type
FROM 
	information_schema.table_constraints AS tc 
	JOIN information_schema.key_column_usage AS kcu
	  ON tc.constraint_name = kcu.constraint_name
	JOIN information_schema.constraint_column_usage AS ccu
	  ON ccu.constraint_name = tc.constraint_name
WHERE ccu.table_name='" . $table . "'");
	}

	function getPlaceholder()
	{
		return '$0$';
	}

	function isPostgres()
	{
		return true;
	}

	/**
	 * @param $table
	 * @param array $columns
	 * @return string
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	function getReplaceQuery($table, array $columns)
	{
		if ($this->getVersion() < 9.5) {
			throw new DatabaseException(__METHOD__ . ' is not working in PG < 9.5. Use runReplaceQuery()');
		}
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = "INSERT INTO {$table} ({$fields}) VALUES ({$values}) 
			ON CONFLICT UPDATE SET ";
		return $q;
	}

	/**
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @param array $primaryKeys ['id', 'id_profile']
	 * @return string
	 * @throws DatabaseException
	 * @throws MustBeStringException
	 */
	function runReplaceQuery($table, array $columns, array $primaryKeys = [])
	{
//		debug($table, $columns, $primaryKeys, $this->getVersion(), $this->getVersion() >= 9.5);
		if ($this->getVersion() >= 9.5) {
			$q = $this->getReplaceQuery($table, $columns);
			die($q);
			return $this->perform($q);
		} else {
//			debug($this->isTransaction());
			//$this->transaction();
//			debug($this->isTransaction());
			$key_key = array_combine($primaryKeys, $primaryKeys);
			$where = array_intersect_key($columns, $key_key);
			$find = $this->runSelectQuery($table, $where);
			$rows = $this->numRows($find);
//			debug($rows, $table, $columns, $where);
//			exit;
			if ($rows) {
				$this->runUpdateQuery($table, $columns, $where);
			} else {
				$this->runInsertQuery($table, $columns);
			}
			//return $this->commit();
		}
	}

	function isTransaction()
	{
		return pg_transaction_status($this->connection) == PGSQL_TRANSACTION_INTRANS;
	}

	function getInfo()
	{
		return pg_version($this->connection) + [
				'options' => pg_options($this->connection),
				'busy' => pg_connection_busy($this->connection),
				'status' => pg_connection_status($this->connection),
				'status_ok' => PGSQL_CONNECTION_OK,
				'status_bad' => PGSQL_CONNECTION_BAD,
				'transaction' => pg_transaction_status($this->connection),
				'client_encoding' => pg_client_encoding($this->connection),
				'host' => pg_host($this->connection),
				'port' => pg_port($this->connection),
			];
	}

}
