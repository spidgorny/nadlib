<?php

/**
 * Class dbLayer
 * @mixin SQLBuilder
 */
class dbLayer extends dbLayerBase implements DBInterface
{

	var $RETURN_NULL = TRUE;

	/**
	 * @var resource
	 */
	protected $CONNECTION = NULL;

	var $LAST_PERFORM_RESULT;
	var $LAST_PERFORM_QUERY;

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
	var $lastQuery;

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
		'SELECT', 'LIKE',
	);

	/**
	 * @param string $dbse
	 * @param string $user
	 * @param string $pass
	 * @param string $host
	 * @throws Exception
	 */
	function __construct($dbse, $user, $pass, $host = "localhost")
	{
		if ($dbse) {
			$this->connect($dbse, $user, $pass, $host);
			//debug(pg_version()); exit();
			$version = pg_version();
			if ($version['server'] >= 8.4) {
				$query = "select * from pg_get_keywords() WHERE catcode IN ('R', 'T')";
				$words = $this->fetchAll($query, 'word');
				$this->reserved = array_keys($words);
				$this->reserved = array_map('strtoupper', $this->reserved); // important
			}
		}
	}

	/**
	 * @return bool
	 */
	function isConnected()
	{
		return !!$this->CONNECTION;
	}

	function connect($dbse, $user, $pass, $host = "localhost")
	{
		$this->database = $dbse;
		$string = "host=$host dbname=$dbse user=$user password=$pass";
		#debug($string);
		#debug_print_backtrace();
		$this->CONNECTION = pg_connect($string);
		if (!$this->CONNECTION) {
			throw new Exception("No postgre connection.");
			//printbr('Error: '.pg_errormessage());	// Warning: pg_errormessage(): No PostgreSQL link opened yet
		} else {
			$this->perform("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;");
		}
		//print(pg_client_encoding($this->CONNECTION));
		return true;
	}

	/**
	 * @param $query
	 * @return null|resource
	 * @throws DatabaseException
	 */
	function perform($query)
	{
		$prof = new Profiler();
		$this->lastQuery = $query;
		if (!is_resource($this->CONNECTION)) {
			debug($query);
			debug_pre_print_backtrace();
		}
		$this->LAST_PERFORM_RESULT = pg_query($this->CONNECTION, $query);
		if (!$this->LAST_PERFORM_RESULT) {
			debug($query);
			debug_pre_print_backtrace();
			throw new DatabaseException(pg_errormessage($this->CONNECTION));
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->queryLog) {
				$this->queryLog->log($query, $prof->elapsed());
			}
		}
		$this->queryCount++;
		return $this->LAST_PERFORM_RESULT;
	}

	function performWithParams($query, $params)
	{
		$prof = new Profiler();
		$this->lastQuery = $query;
		$this->LAST_PERFORM_RESULT = pg_query_params($this->CONNECTION, $query, $params);
		if (!$this->LAST_PERFORM_RESULT) {
			debug($query);
			debug_pre_print_backtrace();
			throw new Exception(pg_errormessage($this->CONNECTION));
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->queryLog) {
				$this->queryLog->log($query, $prof->elapsed());
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
		$meta = pg_meta_data($this->CONNECTION, $table);
		if (is_array($meta)) {
			return array_keys($meta);
		} else {
			error("Table not found: <strong>$table</strong>");
			exit();
		}
	}

	function getTableColumnsEx($table)
	{
		$meta = pg_meta_data($this->CONNECTION, $table);
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
			$meta = pg_meta_data($this->CONNECTION, $table);
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
		$meta = pg_meta_data($this->CONNECTION, $table);
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
				$row['sequence'] = $parts[1];
				$row['sequence'] = str_replace('"', '', $row['sequence']);  // can be quoted
			}
		}
		return ArrayPlus::create($data)->IDalize('column_name')->getData();
	}

	function amountOf($table, $where = "1 = 1")
	{
		return $this->sqlFind("count(*)", $table, $where);
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
		//error('BEGIN');
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
		//error('COMMIT');
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
		//error('ROLLBACK');
		return $this->perform("rollback");
	}

	function quoteSQL($value)
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
		} else {
			return "'" . $this->escape($value) . "'";
		}
	}

	function quoteValues($a)
	{
		$c = array();
		foreach ($a as $b) {
			$c[] = $this->quoteSQL($b);
		}
		return $c;
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
		if (is_string($result)) {
			//debug($result);
			$result = $this->perform($result);
		}
		//debug($this->numRows($result));
		$res = pg_fetch_all($result);
		pg_free_result($result);
		if ($_REQUEST['d'] == 'q') {
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
	 * @param result/query $result
	 * @return array
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

	function __call($method, array $params)
	{
		if (method_exists($this->getQb(), $method)) {
			return call_user_func_array(array($this->getQb(), $method), $params);
		} else {
			throw new Exception('Method ' . __CLASS__ . '::' . $method . ' doesn\'t exist.');
		}
	}

	function quoteKey($key)
	{
		$key = '"' . $key . '"';
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
			'retrieveDataFromDB',
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

}
