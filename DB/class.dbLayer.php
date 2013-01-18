<?php

class dbLayer {
	var $RETURN_NULL = TRUE;
	var $CONNECTION = NULL;
	var $COUNTQUERIES = 0;
	var $LAST_PERFORM_RESULT;
	var $LAST_PERFORM_QUERY;
	var $QUERIES = array();
	var $QUERYMAL = array();
	public $saveQueries = false;

	function dbLayer($dbse = "buglog", $user = "slawa", $pass = "slawa", $host = "localhost") {
		if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		if ($dbse) {
			$this->connect($dbse, $user, $pass, $host);
		}
	}

	function isConnected() {
		return $this->CONNECTION;
	}

	function connect($dbse, $user, $pass, $host = "localhost") {
		$this->CONNECTION = pg_connect("host=$host dbname=$dbse user=$user password=$pass");
		if (!$this->CONNECTION ) {
			printbr("No postgre connection");
			exit();
			return false;
		}
		//print(pg_client_encoding($this->CONNECTION));
		return true;
	}

	function perform($query) {
		$prof = new Profiler();
		$this->LAST_PERFORM_QUERY = $query;
		$this->LAST_PERFORM_RESULT = pg_query($this->CONNECTION, $query);
		if (!$this->LAST_PERFORM_RESULT) {
			debug_pre_print_backtrace();
			throw new Exception(pg_errormessage($this->CONNECTION));
		}
		if (0 || Config::getInstance()->debugQueries) {
			echo 'Query: '.$query.': '.$this->numRows($this->LAST_PERFORM_RESULT).'<br />';
		}
		if ($this->saveQueries) {
			$this->QUERIES[$query] += $prof->elapsed();
			$this->QUERYMAL[$query]++;
		}
		$this->COUNTQUERIES++;
		return $this->LAST_PERFORM_RESULT;
	}

	function sqlFind($what, $from, $where, $returnNull = FALSE, $debug = FALSE) {
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
			return $row[0];
		} else {
			if ($rows == 0 && $returnNull) {
				pg_free_result($result);
				return NULL;
			} else {
				printbr("<b>$query: $rows</b>");
				print_r(pg_fetch_all($result));
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

	function getTableDataSql($query, $key = NULL) {
		$result = $this->perform($query);
		$return = array();
		while ($row = pg_fetch_assoc($result)) {
			if ($key) {
				$return[$row[$key]] = $row;
			} else {
				$return[] = $row;
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
		/*if ($temp = unserialize($value)) {
			$value = $temp;
		}*/
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
		} else if (is_bool($value)) {
			return $value ? "'t'" : "'f'";
		} else if (is_numeric($value)) {
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

	function getSelectQuery($table, $where = array(), $order = "", $select = '*') {
		$q = "select $select from $table ";
		$set = array();
		foreach($where as $key => $val) {
			$val = $this->quoteSQL($val);
			$set[] = "$key = $val";
		}
		if (sizeof($set)) {
			$q .= " where " . implode(" and ", $set);
		}
		$q .= " ".$order;
		return $q;
	}

	function getDeleteQuery($table, $where = array(), $order = "") {
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

	function fetchAssoc($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		return pg_fetch_assoc($res);
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

	function runUpdateQuery($table, $set, $where) {
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
			$res = $this->perform('SELECT LASTVAL() AS lastval');
			$row = $this->fetchAssoc($res);
			$id = $row['lastval'];
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

	function getArrayIntersect(array $options, $field = 'list_next') {
		$bigOR = array();
		foreach ($options as $n) {
			$bigOR[] = "FIND_IN_SET('".$n."', {$field})";
		}
		$bigOR = "(" . implode(' OR ', $bigOR) . ")";
		return $bigOR;
	}

	/**
	 * http://www.php.net/manual/en/ref.pgsql.php#57709
	 *
	 * @param unknown_type $pgArray
	 * @return unknown
	 */
	function PGArrayToPHPArray($pgArray) {
	  $ret = array();
	  $stack = array(&$ret);
	  $pgArray = substr($pgArray, 1, -1);
	  $pgElements = explode(",", $pgArray);

	  //ArrayDump($pgElements);

	  foreach($pgElements as $elem)
	    {
	      if(substr($elem,-1) == "}")
	        {
	          $elem = substr($elem,0,-1);
	          $newSub = array();
	          while(substr($elem,0,1) != "{")
	            {
	              $newSub[] = $elem;
	              $elem = array_pop($ret);
	            }
	          $newSub[] = substr($elem,1);
	          $ret[] = array_reverse($newSub);
	        }
	      else
	        $ret[] = $elem;
	    }
	  return $ret;
	}

	/**
	 * Slawa's own recursive approach. Not working 100%. See mTest from ORS.
	 * @param $dbarr
	 */
	function getPGArray($input) {
		if ($input{0} == '{') {	// array inside
			$input = substr(substr(trim($input), 1), 0, -1);	// cut { and }
			return $this->getPGArray($input);
		} else {
			if (strpos($input, '},{') !== FALSE) {
				$parts = explode('},{', $input);
				foreach ($parts as &$p) {
					$p = $this->getPGArray($p);
				}
			} else {
				$parts = $this->str_getcsv($input, ',', '"');
				$parts = (array)$parts;
				//debug($parts);
				//$parts = array_map('stripslashes', $parts);	// already done in str_getcsv
			}
			return $parts;
		}
	}

	function str_getcsv($input, $delimiter=',', $enclosure='"', $escape='\\', $eol=null) {
		$temp=fopen("php://memory", "rw");
		fwrite($temp, $input);
		fseek($temp, 0);
		$r = array();
		while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
			$r[] = array_map('stripslashes', $data);
		}
		fclose($temp);
		return $r[0];
	}

	/**
	 * Change a db array into a PHP array
	 * @param $arr String representing the DB array
	 * @return A PHP array
	 */
/*	function getPGArray($dbarr) {
		// Take off the first and last characters (the braces)
		$arr = substr($dbarr, 1, strlen($dbarr) - 2);

		// Pick out array entries by carefully parsing.  This is necessary in order
		// to cope with double quotes and commas, etc.
		$elements = array();
		$i = $j = 0;
		$in_quotes = false;
		while ($i < strlen($arr)) {
			// If current char is a double quote and it's not escaped, then
			// enter quoted bit
			$char = substr($arr, $i, 1);
			if ($char == '"' && ($i == 0 || substr($arr, $i - 1, 1) != '\\'))
				$in_quotes = !$in_quotes;
			elseif ($char == ',' && !$in_quotes) {
				// Add text so far to the array
				$elements[] = substr($arr, $j, $i - $j);
				$j = $i + 1;
			}
			$i++;
		}
		// Add final text to the array
		$elements[] = substr($arr, $j);

		// Do one further loop over the elements array to remote double quoting
		// and escaping of double quotes and backslashes
		for ($i = 0; $i < sizeof($elements); $i++) {
			$v = $elements[$i];
			if (strpos($v, '"') === 0) {
				$v = substr($v, 1, strlen($v) - 2);
				$v = str_replace('\\"', '"', $v);
				$v = str_replace('\\\\', '\\', $v);
				$elements[$i] = $v;
			}
		}

		return $elements;
	}
*/
	function getPGArray1D($input) {
		$pgArray = substr(substr(trim($input), 1), 0, -1);
		$v1 = explode(',', $pgArray);
		if ($v1 == array('')) return array();
		$inside = false;
		$out = array();
		$o = 0;
		foreach ($v1 as $word) {
			if ($word{0} == '"') {
				$inside = true;
				$word = substr($word, 1);
			}
			if (in_array($word{strlen($word)-1}, array('"'))
			&& !in_array($word{strlen($word)-2}, array('\\'))
			) {
				$inside = false;
				$word = substr($word, 0, -1);
			}
			$out[$o] .= stripslashes($word); // strange but required
			if (!$inside) {
				$o++;
			}
		}
		//debug($input, $pgArray, $out);
		return $out;
	}

/*	public function getPGArray($text) {
		$this->pg_array_parse($text, $output);
		return $output;
	}

	private function pg_array_parse( $text, &$output, $limit = false, $offset = 1 ) {
		if( false === $limit )
		{
			$limit = strlen( $text )-1;
			$output = array();
		}
		if( '{}' != $text )
			do
			{
				if( '{' != $text{$offset} )
				{
					preg_match( "/(\\{?\"([^\"\\\\]|\\\\.)*\"|[^,{}]+)+([,}]+)/", $text, $match, 0, $offset );
					$offset += strlen( $match[0] );
					$output[] = ( '"' != $match[1]{0} ? $match[1] : stripcslashes( substr( $match[1], 1, -1 ) ) );
					if( '},' == $match[3] ) return $offset;
				}
				else  $offset = $this->pg_array_parse( $text, $output, $limit, $offset+1 );
			}
			while( $limit > $offset );
	}
*/
	function setPGArray(array $data) {
		foreach ($data as &$el) {
			if (is_array($el)) {
				$el = $this->setPGArray($el);
			} else {
				$el = pg_escape_string($el);
				$el = '"'.str_replace(array(
					'"',
				), array(
					'\\"',
				), $el).'"';
			}
		}
		return '{'.implode(',', $data).'}';
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
