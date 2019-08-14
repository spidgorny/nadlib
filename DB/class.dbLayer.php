<?php

/**
 * Class dbLayer
 * @mixin SQLBuilder
 */
class dbLayer extends dbLayerBase
{
	var $RETURN_NULL = TRUE;

	/**
	 * @var resource
	 */
	public $CONNECTION = NULL;

	var $COUNTQUERIES = 0;
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

	/**
	 * logging:
	 */
	public $saveQueries = false;

	var $QUERIES = array();
	var $QUERYMAL = array();
	var $QUERYFUNC = array();

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
	 * @var string DB name
	 */
	var $db;

	function __construct($dbse = "buglog", $user = "slawa", $pass = "slawa", $host = "localhost")
	{
		if ($dbse) {
			$this->connect($dbse, $user, $pass, $host);
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
		$this->db = $dbse;
		$string = "host=$host dbname=$dbse user=$user password=$pass";
		#debug($string);
		#debug_print_backtrace();
		$this->CONNECTION = pg_connect($string);
		if (!$this->CONNECTION) {
			throw new Exception("No postgre connection.");
			//printbr('Error: '.pg_errormessage());	// Warning: pg_errormessage(): No PostgreSQL link opened yet
			return false;
		} else {
			$this->perform("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;");
		}
		//print(pg_client_encoding($this->CONNECTION));
		return true;
	}

	function perform($query)
	{
		$prof = new Profiler();
		$this->LAST_PERFORM_QUERY = $query;
		$this->lastQuery = $query;
		$this->LAST_PERFORM_RESULT = pg_query($this->CONNECTION, $query);
		if (!$this->LAST_PERFORM_RESULT) {
			debug($query);
			debug_pre_print_backtrace();
			throw new Exception(pg_errormessage($this->CONNECTION));
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->saveQueries) {
				@$this->QUERIES[$query] += $prof->elapsed();
				@$this->QUERYMAL[$query]++;
				$this->QUERYFUNC[$query] = $this->getCallerFunction();
			}
		}
		$this->COUNTQUERIES++;
		return $this->LAST_PERFORM_RESULT;
	}

	function performWithParams($query, $params)
	{
		$prof = new Profiler();
		$this->LAST_PERFORM_QUERY = $query;
		$this->lastQuery = $query;
		$this->LAST_PERFORM_RESULT = pg_query_params($this->CONNECTION, $query, $params);
		if (!$this->LAST_PERFORM_RESULT) {
			debug($query);
			debug_pre_print_backtrace();
			throw new Exception(pg_errormessage($this->CONNECTION));
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->saveQueries) {
				@$this->QUERIES[$query] += $prof->elapsed();
				@$this->QUERYMAL[$query]++;
				$this->QUERYFUNC[$query] = $this->getCallerFunction();
			}
		}
		$this->COUNTQUERIES++;
		return $this->LAST_PERFORM_RESULT;
	}

	function sqlFind($what, $from, $where, $returnNull = FALSE, $debug = FALSE)
	{
		$trace = $this->getCallerFunction();
		if (isset($GLOBALS['profiler'])) @$GLOBALS['profiler']->startTimer(__METHOD__ . ' (' . $from . ')' . ' // ' . $trace['class'] . '::' . $trace['function']);
		$query = "select ($what) as res from $from where $where";
		if ($debug) printbr("<b>$query</b>");
		$result = $this->perform($query);
		$rows = pg_num_rows($result);
		if ($rows == 1) {
			$row = pg_fetch_row($result, 0);
			pg_free_result($result);
//			printbr("<b>$query: $row[0]</b>");
			$return = $row[0];
		} else {
			if ($rows == 0 && $returnNull) {
				pg_free_result($result);
				$return = NULL;
			} else {
				printbr("<b>$query: $rows</b>");
				printbr("ERROR: No result or more than one result of sqlFind()");
				debug_pre_print_backtrace();
				exit();
			}
		}
		if (isset($GLOBALS['profiler'])) @$GLOBALS['profiler']->stopTimer(__METHOD__ . ' (' . $from . ')' . ' // ' . $trace['class'] . '::' . $trace['function']);
		return $return;
	}

	function sqlFindRow($query)
	{
		$result = $this->perform($query);
		if ($result && pg_num_rows($result)) {
			$a = pg_fetch_assoc($result, 0);
			pg_free_result($result);
			return $a;
		} else {
			return array();
		}
	}

	function sqlFindSql($query)
	{
		$result = $this->perform($query);
		$a = pg_fetch_row($result, 0);
		return $a[0];
	}

	/**
	 * Return one dimensional array
	 * @param $table
	 * @return array
	 */
	function getTableColumns($table)
	{
		$meta = pg_meta_data($this->CONNECTION, $table);
		if (is_array($meta)) {
			return array_keys($meta);
		} else {
			error("Table not found: <b>$table</b>");
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
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
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
				error("Table not found: <b>$table</b>");
				exit();
			}
		}
		$return = $cache[$table];
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
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
			error("Table not found: <b>$table</b>");
			exit();
		}
	}

	function getTableDataEx($table, $where = "", $what = "*")
	{
		$query = "select " . $what . " from $table";
		if (!empty($where)) $query .= " where $where";
		$result = $this->fetchAll($query);
		return $result;
	}

	function getTableOptions($table, $column, $where = "", $key = 'id')
	{
		$tableName = $this->getFirstWord($table);
		$a = $this->getTableDataEx($table, $where, $tableName . '.*, ' . $column);

		// select login.*, coalesce(name, '') || ' ' || coalesce(surname, '') AS combined from login where relcompany = '47493'
		$as = trimExplode(' AS ', $column);
		if ($as[1]) {
			$column = $as[1];
		}

		$b = array();
		foreach ($a as $row) {
			$b[$row[$key]] = $row[$column];
		}
		return $b;
	}

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
		where
		not relname ~ 'pg_.*'
		and not relname ~ 'sql_.*'
		and relkind = 'r'
		ORDER BY relname";
		$result = $this->perform($query);
		$return = pg_fetch_all($result);
		pg_free_result($result);
		return ArrayPlus::create($return)->column('relname');
	}

	function amountOf($table, $where = "1 = 1")
	{
		return $this->sqlFind("count(*)", $table, $where);
	}

	function dataSeek($res, $number)
	{
		$ok = pg_result_seek($res, $number);
		if (!$ok) {
			throw new DatabaseException('pg_result_seek failed');
		}
		return $ok;
	}

	function transaction($serializable = false)
	{
		if ($serializable) {
			$this->perform('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
		}
		return $this->perform("BEGIN");
	}

	function commit()
	{
		return $this->perform("commit");
	}

	function rollback()
	{
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
	 * @param string $table Table name
	 * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
	 * @return string
	 */
	function getInsertQuery($table, $columns)
	{
		$q = 'INSERT INTO ' . $table . ' (';
		$q .= implode(", ", array_keys($columns));
		$q .= ") VALUES (";
		$q .= implode(", ", $this->quoteValues(array_values($columns)));
		$q .= ")";
		return $q;
	}

	function fetchAll($result, $key = NULL)
	{
		if (is_string($result)) {
			$result = $this->perform($result);
		}
		$res = pg_fetch_all($result);
		if ($_REQUEST['d'] == 'q') {
			debug($this->lastQuery, sizeof($res));
		}
		if (!$res) {
			$res = array();
		} else if ($key) {
			$res = ArrayPlus::create($res)->IDalize($key)->getData();
		}

		pg_free_result($result);
		return $res;
	}

	/**
	 * @param string $table
	 * @param array $columns
	 * @param array $where
	 * @return string
	 */
	function getUpdateQuery($table, $columns, $where)
	{
		$q = 'UPDATE ' . $table . ' SET ';
		$set = array();
		foreach ($columns as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		$q .= implode(", ", $set);
		$q .= " WHERE ";
		$set = array();
		foreach ($where as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		$q .= implode(" AND ", $set);
		return $q;
	}

	function getFirstWord($table)
	{
		$table1 = explode(' ', $table);
		$table1 = $table1[0];
		return $table1;
	}

	function getDeleteQuery($table, array $where, $what = '')
	{
		$q = "DELETE " . $what . " FROM $table ";
		$set = array();
		foreach ($where as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		if (sizeof($set)) {
			$q .= " WHERE " . implode(" AND ", $set);
		} else {
			$q .= ' WHERE 1 = 0';
		}
		return $q;
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
		if (!$row) {
			$row = array();
		}
		return $row;
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

	function runSelectQuery($table, $where = array(), $order = '', $select = '*')
	{
		$query = $this->getSelectQuery($table, $where, $order, $select);
		$res = $this->perform($query);
		return $res;
	}

	function numRows($query)
	{
		if (is_string($query)) {
			$query = $this->perform($query);
		}
		return pg_num_rows($query);
	}

	function runUpdateQuery($table, array $set, array $where)
	{
		$query = $this->getUpdateQuery($table, $set, $where);
		return $this->perform($query);
	}

	function getLastInsertID($res, $table = 'not required since 8.1')
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

	/**
	 * This used to retrieve a single row !!!
	 * @param $table
	 * @param $where
	 * @param string $order
	 * @param string $selectPlus
	 * @param null $idField
	 * @return array
	 */
	function fetchSelectQuery($table, $where, $order = '', $selectPlus = '', $idField = NULL)
	{
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$row = $this->fetchAll($res, $idField);
		return $row;
	}

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '', $only = FALSE)
	{
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$row = $this->fetchAssoc($res);
		return $row;
	}

	/**
	 *
	 * @param type $table
	 * @param array $where
	 * @param string $order
	 * @param string $selectPlus
	 * @param $key
	 * @return table
	 */
	function fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = NULL)
	{
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$rows = $this->fetchAll($res, $key);
		return $rows;
	}

	function runInsertUpdateQuery($table, $fields, $where, $createPlus = array())
	{
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->transaction();
		$res = $this->runSelectQuery($table, $where);
		$this->found = $this->fetchAssoc($res);
		if ($this->found) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$res = $this->perform($query);
			$inserted = $this->found['id'];
		} else {
			$query = $this->getInsertQuery($table, $fields + $createPlus);
			$res = $this->perform($query);
			$inserted = $this->getLastInsertID($res, $table);
			//$inserted = $this->lastval(); should not be used directly
		}
		$this->commit();
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $inserted;
	}

	function runDeleteQuery($table, $where)
	{
		$this->perform($this->getDeleteQuery($table, $where));
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
		$rows = slArray::column_assoc($rows, 'comment', 'colname');
		return $rows[$column];
	}

	/**
	 * Uses find_in_set function which is not built-in
	 * @param array $options
	 * @param string $field
	 * @return string
	 * @see SQLBuilder::array_intersect()
	 *
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

	function runUpdateInsert($table, $set, $where)
	{
		$found = $this->runSelectQuery($table, $where);
		if ($this->numRows($found)) {
			$res = 'update';
			$this->runUpdateQuery($table, $set, $where);
		} else {
			$res = 'insert';
			$this->runInsertQuery($table, $set + $where);
		}
		return $res;
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
		foreach (range(1, 2) as $_) {
			$func = current($debug);
			$func['line'] = $prev['line'];    // line is from the parent function?
			$content[] = $func['class'] . '::' . $func['function'] . '#' . $func['line'];
			next($debug);
		}
		$content = implode(' < ', $content);
		return $content;
	}

	function affectedRows($res) {
		return pg_affected_rows($res);
	}

	/**
	 * Renders the list of queries accumulated
	 * @return string
	 */
	function dumpQueries()
	{
		$q = $this->QUERIES;
		arsort($q);
		foreach ($q as $query => &$time) {
			$times = $this->QUERYMAL[$query];
			$time = array(
				'times' => $times,
				'query' => $query,
				'time' => number_format($time, 3),
				'time/1' => number_format($time / $times, 3),
				'func' => $this->QUERYFUNC[$query],
			);
		}
		$q = new slTable($q, 'class="view_array" width="1024"', array(
			'times' => 'Times',
			'time' => array(
				'name' => 'Time',
				'align' => 'right',
			),
			'time/1' => array(
				'name' => 'Time/1',
				'align' => 'right',
			),
			'query' => 'Query',
			'func' => 'Caller',
		));
		$q->isOddEven = false;
		$content = '<div class="profiler">' . $q . '</div>';
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

	public function getQb()
	{
		if (!isset($this->qb)) {
			$this->setQb(new SQLBuilder(Config::getInstance()->db));
		}

		return $this->qb;
	}
}
