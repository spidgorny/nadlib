<?php

class dbLayer {
	var $RETURN_NULL = TRUE;
	var $CONNECTION = NULL;
	var $COUNTQUERIES = 0;
	var $LAST_PERFORM_RESULT;
	var $LAST_PERFORM_QUERY;
	
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

	function dbLayer($dbse = "buglog", $user = "slawa", $pass = "slawa", $host = "localhost") {
		if ($dbse) {
			$this->connect($dbse, $user, $pass, $host);
		}
	}

	/**
	 * @return bool
	 */
	function isConnected() {
		return !!$this->CONNECTION;
	}

	function connect($dbse, $user, $pass, $host = "localhost") {
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

	function perform($query) {
		$prof = new Profiler();
		$this->LAST_PERFORM_QUERY = $query;
		$this->lastQuery = $query;
		$this->LAST_PERFORM_RESULT = pg_query($this->CONNECTION, $query);
		if (!$this->LAST_PERFORM_RESULT) {
			debug_pre_print_backtrace();
			debug($query);
			throw new Exception(pg_errormessage($this->CONNECTION));
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->saveQueries) {
				@$this->QUERIES[$query] += $prof->elapsed();
				@$this->QUERYMAL[$query]++;
				$this->QUERYFUNC[$query] = $this->getCallerFunction();
				$this->QUERYFUNC[$query] = $this->QUERYFUNC[$query]['class'].'::'.$this->QUERYFUNC[$query]['function'];
			}
		}
		$this->COUNTQUERIES++;
		return $this->LAST_PERFORM_RESULT;
	}

	function sqlFind($what, $from, $where, $returnNull = FALSE, $debug = FALSE) {
		$debug = $this->getCallerFunction();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.' ('.$from.')'.' // '.$debug['class'].'::'.$debug['function']);
		$query = "select ($what) as res from $from where $where";
		//print $where."<br>";
		//print $query."<br>";
		if ($from == 'buglog' && 1) {
			//printbr("<b>$query: $row[0]</b>");
		}
		$result = $this->perform($query);
		$rows = pg_num_rows($result);
		if ($rows == 1) {
			$row = pg_fetch_row($result, 0);
//			printbr("<b>$query: $row[0]</b>");
			$return = $row[0];
		} else {
			if ($rows == 0 && $returnNull) {
				pg_free_result($result);
				$return = NULL;
			} else {
				printbr("<b>$query: $rows</b>");
				printbr("ERROR: No result or more than one result of sqlFind()");
				my_print_backtrace($query);
				exit();
			}
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.' ('.$from.')'.' // '.$debug['class'].'::'.$debug['function']);
		return $return;
	}

	function sqlFindRow($query) {
		$result = $this->perform($query);
		if ($result && pg_num_rows($result)) {
			$a = pg_fetch_assoc($result, 0);
			pg_free_result($result);
			return $a;
		} else {
			return array();
		}
	}

	function sqlFindSql($query) {
		$result = $this->perform($query);
		$a = pg_fetch_row($result, 0);
		return $a[0];
	}

	function getTableColumns($table) {
		$meta = pg_meta_data($this->CONNECTION, $table);
		if (is_array($meta)) {
			return array_keys($meta);
		} else {
			error("Table not found: <b>$table</b>");
			exit();
		}
	}

	function getTableColumnsCached($table) {
		//debug($table); exit;
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (!$this->mcaTableColumns) {
			$this->mcaTableColumns = new MemcacheArray(__CLASS__.'.'.__FUNCTION__, 24 * 60 * 60);
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

		//debug($return); exit;
		//print "<pre>";
		//print_r($return);
		return $return;
	}

	function getColumnTypes($table) {
		$meta = pg_meta_data($this->CONNECTION, $table);
		if (is_array($meta)) {
			$return = array();
			foreach($meta as $col => $m) {
				$return[$col] = $m['type'];
			}
			return $return;
		} else {
			error("Table not found: <b>$table</b>");
			exit();
		}
	}

	function getTableDataEx($table, $where = "", $what = "*") {
		$query = "select ".$what." from $table";
		if (!empty($where)) $query .= " where $where";
		$result = $this->fetchAll($query);
		return $result;
	}

	function getTableOptions($table, $column, $where = "", $key = 'id') {
		$a = $this->getTableDataEx($table, $where, $column);
		$b = array();
		foreach ($a as $row) {
			$b[$row[$key]] = $row["special"];
		}
		//debug($this->LAST_PERFORM_QUERY, $a, $b);
		return $b;
	}

	/**
	 * fetchAll() equivalent with $key and $val properties
	 * @param $query
	 * @param null $key
	 * @param null $val
	 * @return array
	 */
	function getTableDataSql($query, $key = NULL, $val = NULL) {
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
	function getTables() {
		$query = "select relname from pg_class where not relname ~ 'pg_.*' and not relname ~ 'sql_.*' and relkind = 'r'";
		$result = $this->perform($query);
		$return = pg_fetch_all($result);
		pg_free_result($result);
		return array_column($return, 'relname');
	}

	function amountOf($table, $where = "1 = 1") {
		return $this->sqlFind("count(*)", $table, $where);
	}

	function transaction() {
		//$this->perform("set autocommit = off");
		$this->perform('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
		return $this->perform("BEGIN");
	}

	function commit() {
		return $this->perform("commit");
	}

	function rollback() {
		return $this->perform("rollback");
	}

	function quoteSQL($value) {
		if ($value === NULL) {
			return "NULL";
		} else if ($value === FALSE) {
			return "'f'";
		} else if ($value === TRUE) {
			return "'t'";
		} else if (is_int($value)) {	// is_numeric - bad: operator does not exist: character varying = integer
			return $value;
		} else if (is_bool($value)) {
			return $value ? "'t'" : "'f'";
		} else if ($value instanceof SQLParam) {
			return $value;
		} else {
			return "'".$this->escape($value)."'";
		}
	}

	function quoteValues($a) {
		$c = array();
		foreach ($a as $b) {
			$c[] = $this->quoteSQL($b);
		}
		return $c;
	}

	function getInsertQuery($table, $columns) {
		$q = "insert into $table (";
		$q .= implode(", ", array_keys($columns));
		$q .= ") values (";
		$q .= implode(", ", $this->quoteValues(array_values($columns)));
		$q .= ")";
		return $q;
	}

	function fetchAll($result, $key = NULL) {
		if (is_string($result)) {
			$result = $this->perform($result);
		}
		$res = pg_fetch_all($result);
		if ($_REQUEST['d'] == 'q') {
			debug($this->lastQuery, sizeof($res));
		}
		if ($res && $key) {
			$res = ArrayPlus::create($res)->IDalize($key)->getData();
		}
		if (!$res) {
			$res = array();
		}
		pg_free_result($result);
		return $res;
	}

	function getUpdateQuery($table, $columns, $where) {
		$q = "update $table set ";
		$set = array();
		foreach($columns as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		$q .= implode(", ", $set);
		$q .= " where ";
		$set = array();
		foreach($where as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		$q .= implode(" and ", $set);
		return $q;
	}

	function getFirstWord($table) {
		$table1 = explode(' ', $table);
		$table1 = $table1[0];
		return $table1;
	}

	function getDeleteQuery($table, array $where) {
		$q = "delete from $table ";
		$set = array();
		foreach($where as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		if (sizeof($set)) {
			$q .= " where " . implode(" and ", $set);
		} else {
			$q .= ' where 1 = 0';
		}
		return $q;
	}

	function getAllRows($query) {
		$result = $this->perform($query);
		$data = $this->fetchAll($result);
		return $data;
	}

	function getFirstRow($query) {
		$result = $this->perform($query);
		$row = pg_fetch_assoc($result);
		return $row;
	}

	/**
	 * @param result/query $result
	 * @return array
	 */
	function fetchAssoc($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = pg_fetch_assoc($res);
		return $row;
	}

	function getFirstValue($query) {
		$result = $this->perform($query);
		$row = pg_fetch_row($result);
		$value = $row[0];
		return $value;
	}

	function runSelectQuery($table, $where = array(), $order = '', $addSelect = '', $doReplace = false) {
		$query = $this->getSelectQuery($table, $where, $order, $addSelect, $doReplace);
		$res = $this->perform($query);
		return $res;
	}

	function numRows($query) {
		if (is_string($query)) {
			$query = $this->perform($query);
		}
		return pg_num_rows($query);
	}

	function runUpdateQuery($table, array $set, array $where) {
		$query = $this->getUpdateQuery($table, $set, $where);
		return $this->perform($query);
	}

	function getLastInsertID($res, $table = 'not required since 8.1') {
		$pgv = pg_version();
		if ($pgv['server'] >= 8.1) {
			$id = $this->lastval();
		} else {
			$oid = pg_last_oid($res);
			$id = $this->sqlFind('id', $table, "oid = '".$oid."'");
		}
		return $id;
	}

	/**
	 * Compatibility.
	 * @param $res
	 * @param $table
	 * @return null
	 */
	function lastInsertID($res, $table) {
		return $this->getLastInsertID($res, $table);
	}

 	protected function lastval() {
		$res = $this->perform('SELECT LASTVAL() AS lastval');
		$row = $this->fetchAssoc($res);
		$lv = $row['lastval'];
		return $lv;
	}

	function fetchSelectQuery($table, $where, $order = '', $selectPlus = '') {
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
	function fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key) {
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$rows = $this->fetchAll($res, $key);
		return $rows;
	}

	function runInsertUpdateQuery($table, $fields, $where, $createPlus = array()) {
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

	function runDeleteQuery($table, $where) {
		$this->perform($this->getDeleteQuery($table, $where));
	}

	function getComment($table, $column) {
		$query = 'select
     a.attname  as "colname"
    ,a.attrelid as "tableoid"
    ,a.attnum   as "columnoid"
	,col_description(a.attrelid, a.attnum) as "comment"
from
    pg_catalog.pg_attribute a
    inner join pg_catalog.pg_class c on a.attrelid = c.oid
where
        c.relname = '.$this->quoteSQL($table).'
    and a.attnum > 0
    and a.attisdropped is false
    and pg_catalog.pg_table_is_visible(c.oid)
order by a.attnum';
		$rows = $this->fetchAll($query);
		$rows = slArray::column_assoc($rows, 'comment', 'colname');
		return $rows[$column];
	}

	function getArrayIntersect(array $options, $field = 'list_next') {
		$bigOR = array();
		foreach ($options as $n) {
			$bigOR[] = "FIND_IN_SET('".$n."', {$field})";
		}
		$bigOR = "(" . implode(' OR ', $bigOR) . ")";
		return $bigOR;
	}

	function escape($str) {
		debug_pre_print_backtrace();
		return pg_escape_string($str);
	}

	function __call($method, array $params) {
		$qb = class_exists('Config') ? Config::getInstance()->qb : new stdClass();
		if (method_exists($qb, $method)) {
			//debug_pre_print_backtrace();
			return call_user_func_array(array($qb, $method), $params);
		} else {
			throw new Exception('Method '.__CLASS__.'::'.$method.' doesn\'t exist.');
		}
	}

	function quoteKey($key) {
		$key = '"'.$key.'"';
		return $key;
	}
	
	function runUpdateInsert($table, $set, $where) {
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

	function getCallerFunction() {
		$skipFunctions = array(
			'runSelectQuery',
			'fetchSelectQuery',
			'sqlFind',
			'getAllRows',
			'perform',
		);
		$debug = debug_backtrace();
		array_shift($debug);
		while (sizeof($debug) && in_array($debug[0]['function'], $skipFunctions)) {
			array_shift($debug);
		}
		reset($debug);
		$debug = current($debug);
		return $debug;
	}

}
