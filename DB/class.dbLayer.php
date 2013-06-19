<?php

class dbLayer {
	var $RETURN_NULL = TRUE;
	var $CONNECTION = NULL;
	var $COUNTQUERIES = 0;
	var $LAST_PERFORM_RESULT;
	var $LAST_PERFORM_QUERY;
	var $QUERIES = array();
	var $QUERYMAL = array();
	var $AFFECTED_ROWS = NULL;
	const NO_QUOTE = 'NO_QUOTE';

	/**
	 * @var string
	 */
	var $lastQuery;

	function dbLayer($dbse = "buglog", $user = "slawa", $pass = "slawa", $host = "localhost") {
		if ($dbse) {
			$this->connect($dbse, $user, $pass, $host);
		}
	}

	function isConnected() {
		return $this->CONNECTION;
	}

	function connect($dbse, $user, $pass, $host = "localhost") {
		$string = "host=$host dbname=$dbse user=$user password=$pass";
		#debug($string);
		#debug_print_backtrace();
		$this->CONNECTION = pg_connect($string);
		if (!$this->CONNECTION) {
			printbr("No postgre connection.");
			printbr('Error: '.pg_errormessage());
			exit();
			return false;
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
			throw new Exception(pg_errormessage($this->CONNECTION));
		} else {
			$this->AFFECTED_ROWS = pg_affected_rows($this->LAST_PERFORM_RESULT);
			if ($this->saveQueries) {
				@$this->QUERIES[$query] += $prof->elapsed();
				@$this->QUERYMAL[$query]++;
			}
		}
		$this->COUNTQUERIES++;
		return $this->LAST_PERFORM_RESULT;
	}

	function sqlFind($what, $from, $where, $returnNull = FALSE, $debug = FALSE) {
		$query = "select ($what) as res from $from where $where";
		if ($from == 'buglog' && 1) {
			//printbr("<b>$query: $row[0]</b>");
		}
		$result = $this->perform($query);
		$rows = pg_num_rows($result);
		if ($rows == 1) {
			$row = pg_fetch_row($result, 0);
//			printbr("<b>$query: $row[0]</b>");
			return $row[0];
		} else {
			if ($rows == 0 && $returnNull) {
				pg_free_result($result);
				return NULL;
			} else {
				printbr("<b>$query: $rows</b>");
				printbr("ERROR: No result or more than one result of sqlFind()");
				my_print_backtrace($query);
				exit();
			}
		}
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

	function addRow($table, $add) {
		$columns = $this->getTableColumns($table);
		$types = $this->getColumnTypes($table);
		$query = "insert into $table (";
		foreach ($columns as $column) {
			if (!in_array($column, array("id", "relvideo"))) {
				$query .= "" . $column . ", ";
			}
		}
		$query = substr($query, 0, strlen($query)-2);
		$query .= ") values (";
		foreach ($columns as $column) {
			if ($column != "id") {
				if ((empty($add[$column]) && $add[$column] != "0") || $add[$column] == "NULL") {
					$query .= "NULL, ";
				} else {
					$query .= "'" . pg_escape_string(strip_tags($add[$column])) . "', ";
				}
			}
			$columnNr++;
		}
		$query = substr($query, 0, strlen($query)-2);
		$query .= ");";
		return $this->perform($query);
	}

	function getTableDataEx($table, $where = "", $special = "") {
		$query = "select ".($special?$special." as special, ":'')."* from $table";
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

	function setPref($code, $value, $user = null) {
		if ($user != null) {
			$dbValue = $this->sqlFind("value", "prefs", "code = '$code' and relUser = '$user'", $this->RETURN_NULL);
		} else {
			$dbValue = $this->sqlFind("value", "prefs", "code = '$code'", $this->RETURN_NULL);
		}
		if ($dbValue === $value) {
			return; 		// allready there
		} else if ($dbValue === NULL) { 			// no such data
			//printbr($code);
			if ($user != null) {
				$query = "insert into prefs (code, value, reluser) values ('$code', '$value', '$user')";
			} else {
				$query = "insert into prefs (code, value) values ('$code', '$value')";
			}
			$this->perform($query);
		} else { 							// different value in DB
			if (is_array($value)) {
				$value = serialize($value);
			}
			if ($user != null) {
				$query = "update prefs set value = '$value' where code = '$code' and reluser = '$user'";
			} else {
				$query = "update prefs set value = '$value' where code = '$code'";
			}
			$this->perform($query);
		}
	}

	/**
	 * User $this->user->prefs[] instead
	 *
	 * @param $code
	 * @param null $user
	 * @return mixed
	 */
	function getPref($code, $user = null) {
		if ($user != null) {
			$a = $this->sqlFindRow("select value from prefs where code = '$code' and reluser = '$user'");
		} else {
			$a = $this->sqlFindRow("select value from prefs where code = '$code'");
		}
		$value = $a['value'];
		$vs = unserialize($value);
		if ($vs !== FALSE || $value == serialize(FALSE)) {
			$value = $vs;
		}
		return $value;
	}

	function transaction() {
		$this->perform("begin");
	}

	function commit() {
		return $this->perform("commit");
	}

	function rollback() {
		$this->perform("rollback");
	}

	function quoteSQL($value) {
		if ($value === NULL) {
			return "NULL";
		} else if ($value === FALSE) {
			return "'f'";
		} else if ($value === TRUE) {
			return "'t'";
		} else if (is_numeric($value)) {
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
		foreach($a as $b) {
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

	function getSelectQuery($table, $where = array(), $order = "", $selectPlus = '') {
		$table1 = $this->getFirstWord($table);
		if ($selectPlus instanceof Wrap) {
			$what = $selectPlus->wrap($table1.'*');
		} else {
			$what = "$table1.* $selectPlus";
		}
		$q = "select ".$what." from $table ";
		$set = array();
		foreach ($where as $key => $val) {
			if ($val === NULL) {
				$set[] = "$key IS NULL";
			} else if ($val === dbLayer1::NO_QUOTE) {
				$set[] = $key;
			} else {
				$val = $this->quoteSQL($val);
				$set[] = "$key = $val";
			}
		}
		if (sizeof($set)) {
			$q .= " where " . implode(" and ", $set);
		}
		$q .= " ".$order;
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

	function fetchAll($result) {
		if (is_string($result)) {
			$result = $this->perform($result);
		}
		$rows = pg_fetch_all($result);
		if (!$rows) $rows = array();
		return $rows;
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

	function runSelectQuery($table, $where = array(), $order = '', $select = '*') {
		$query = $this->getSelectQuery($table, $where, $order, $select);
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

	function runInsertQuery($table, array $insert) {
		$query = $this->getInsertQuery($table, $insert);
		$res = $this->perform($query);
		$newID = $this->getLastInsertID($res, $table);
		return $newID;
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
	 * @param type $where
	 * @param string $order
	 * @param string $selectPlus
	 * @return table
	 */
	function fetchAllSelectQuery($table, $where, $order = '', $selectPlus = '') {
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$rows = $this->fetchAll($res);
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
		return pg_escape_string($str);
	}

	function __call($method, array $params) {
		$qb = Config::getInstance()->qb;
		if (method_exists($qb, $method)) {
			return call_user_func_array(array($qb, $method), $params);
		} else {
			throw new Exception('Method '.__CLASS__.'::'.$method.' doesn\'t exist.');
		}
	}

	function quoteKey($key) {
		$key = '"'.$key.'"';
		return $key;
	}

}
