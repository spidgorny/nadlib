<?php

/**
 * Class SQLBuilder - contains database unspecific (general) SQL functions.
 * It has $this->db a database specific (PostgreSQL, MySQL, SQLite, Oracle, PDO) class
 * which is performing the actual queries.
 * This $db class has a back reference to this as $this->db->qb == $this.
 * Usage in controllers/models:
 * $this->db = new MySQL();
 * $this->db->qb = new SQLBuilder();
 * $this->db->qb->db = $this->db;
 * $this->db->fetchSelectQuery(...);
 *
 * Note that the creation of objects above is handled by DIContainer
 * but it's not shown above for comprehensibility.
 * @mixin dbLayerBase
 */
class SQLBuilder {

	/**
	 * Update/Insert is storing the found row for debugging
	 * @var mixed
	 */
	public $found;

	/**
	 * @var MySQL
	 */
	public $db;

	function __construct(DIContainer $di) {
		if ($di instanceof DIContainer && $di->db) {
			$this->db = $di->db;
		} else {
			$this->db = Config::getInstance()->db;
		}
	}

	function quoteKey($key) {
		$reserved = $this->getReserved();
		if (in_array(strtoupper($key), $reserved)) {
			$key = $this->db->quoteKey($key);
		}
		return $key;
	}

	/**
	 * Used to really quote different values so that they can be attached to "field = "
	 *
	 * @param $value
	 * @param null $key
	 * @throws MustBeStringException
	 * @return string
	 */
	function quoteSQL($value, $key = NULL) {
		if ($value instanceof AsIs) {
			$value->injectDB($this->db);
			$value->injectQB($this);
			$value->injectField($key);
			return $value->__toString();
		} else if ($value instanceof AsIsOp) {
			//$value->injectQB($this);
			//$value->injectField($key);
			return $value->__toString();
		} else if ($value instanceof SQLOr) {
			return $value->__toString();
		} elseif ($value instanceof IndTime) {
			return $this->quoteSQL($value->getMySQL(), $key);
		} else if ($value instanceof Time) {
			return "'".$this->db->escape($value->toSQL())."'";
		} else if ($value instanceof SQLDate) {
			return "'".$this->db->escape($value->__toString())."'";
		} else if ($value instanceof SimpleXMLElement) {
			return "COMPRESS('".$this->db->escape($value->asXML())."')";
		} else if (is_object($value)) {
			return "'".$this->db->escape($value)."'";
		} else if ($value === NULL) {
			return "NULL";
		} else if (is_numeric($value) && !$this->isExp($value)) {
			//$set[] = "($key = ".$val." OR {$key} = '".$val."')";
			return "'".$value."'";		// quoting will not hurt, but will keep leading zeroes if necessary
		} else if (is_bool($value)) {
			return $this->db->escapeBool($value);
		} else {
			if (is_scalar($value)) {
				return "'".$this->db->escape($value)."'";
			} else {
				debug($key, $value);
				throw new MustBeStringException('Must be string.');
			}
		}
	}

	/**
	 * http://stackoverflow.com/a/4964120
	 * @param $number
	 * @return bool
	 */
	function isExp($number) {
		return is_numeric($number) && $number != number_format($number, 0, '', '');
	}

	/**
	 * Quotes the complete array if necessary.
	 *
	 * @param array $a
	 * @return array
	 */
	function quoteValues(array $a) {
		$c = array();
		foreach($a as $key => $b) {
			$c[] = SQLBuilder::quoteSQL($b, $key);
		}
		return $c;
	}

	/**
	 * Quotes the values as quoteValues does, but also puts the key out and the correct comparison.
	 * In other words, it takes care of col = 'NULL' situation and makes it col IS NULL
	 *
	 * @param array $where
	 * @throws Exception
	 * @throws MustBeStringException
	 * @return array
	 */
	function quoteWhere(array $where) {
		$set = array();
		foreach ($where as $key => $val) {
			if ($key{strlen($key)-1} != '.') {
				$key = $this->quoteKey($key);
				if ($val instanceof AsIs) {
					$val->injectDB($this->db);
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $key . ' = ' . $val;
				} elseif ($val instanceof AsIsOp) {
					if (is_numeric($key)) {
						$set[] = $val;
					} else {
						$set[] = $key . ' ' . $val;
					}
				} else if ($val instanceof SQLBetween) {
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $val->toString($key);
				} else if ($val instanceof SQLWherePart) {
					$val->injectQB($this);
					$val->injectField($key);
					$set[] = $val->__toString();
				} else if ($val instanceof SimpleXMLElement) {
					$set[] = $val->asXML();
				//} else if (is_object($val)) {	// what's that for? SQLWherePart has been taken care of
				//	$set[] = $val.'';
				} else if (isset($where[$key.'.']) && $where[$key.'.']['asis']) {
					if (strpos($val, '###FIELD###') !== FALSE) {
						$val = str_replace('###FIELD###', $key, $val);
						$set[] = $val;
					} else {
						$set[] = '('.$key . ' ' . $val.')';	// for GloRe compatibility - may contain OR
					}
				} else if ($val === NULL) {
					$set[] = "$key IS NULL";
				} else if ($val === 'NOTNULL') {
					$set[] = "$key IS NOT NULL";
				} else if (in_array($key{strlen($key)-1}, array('>', '<'))
                    || in_array(substr($key, -2), array('!=', '<=', '>=', '<>'))) {
					list($key, $sign) = explode(' ', $key); // need to quote separately
					$key = $this->quoteKey($key);
					$set[] = "$key $sign '$val'";
				} else if (is_bool($val)) {
					$set[] = ($val ? "" : "NOT ") . $key;
				} else if (is_numeric($key)) {		// KEY!!!
					$set[] = $val;
				} else if (is_array($val) && $where[$key.'.']['makeIN']) {
					$set[] = $key." IN ('".implode("', '", $val)."')";
				} else if (is_array($val) && $where[$key.'.']['makeOR']) {
					foreach ($val as &$row) {
						if (is_null($row)) {
							$row = $key .' IS NULL';
						} else {
							$row = $key . " = '" . $row . "'";
						}
					}
					$or = new SQLOr($val);
					$or->injectQB($this);
					$set[] = $or;
				} else {
					//debug_pre_print_backtrace();
					try {
						$val = SQLBuilder::quoteSQL($val);
					} catch (MustBeStringException $e) {
						debug($key);
						throw $e;
					}
					$set[] = "$key = $val";
				}
			}
		}
		return $set;

	}

	function getInsertQuery($table, array $columns) {
		$set = $this->quoteLike($columns, '$key');
		$set = implode(", ", $set);

		//$values = $this->quoteLike($columns, '$val');
		$values = array_values($columns);
		$values = $this->quoteValues($values);
		$values = implode(", ", $values);

		$q = "INSERT INTO ".$this->quoteKey($table)."\n($set)\nVALUES ($values)";
		return $q;
	}

	function quoteLike($columns, $like) {
		$set = array();
		foreach ($columns as $key => $val) {
			$key = $this->quoteKey($key);
			$val = $this->quoteSQL($val, $key);
			$from = array('$key', '$val');
			$to = array($key, $val);
			$set[] = str_replace($from, $to, $like);
		}
		//d($_POST, $_REQUEST, $columns, $set, ini_get('magic_quotes_gpc'), get_magic_quotes_gpc(), get_magic_quotes_runtime());
		return $set;
	}

	function getUpdateQuery($table, $columns, $where) {
		//$columns['mtime'] = date('Y-m-d H:i:s');
		$q = "UPDATE $table\nSET ";
		$set = $this->quoteLike($columns, '$key = $val');
		$q .= implode(",\n", $set);
		$q .= "\nWHERE\n";
		$q .= implode("\nAND ", $this->quoteWhere($where));
		return $q;
	}

	function getFirstWord($table) {
		$table1 = explode(' ', $table);
		$table0 = $table1[0];
		//debug($table, $table1, $table0);
		return $table0;
	}

	function getSelectQuery($table, array $where = array(), $order = "", $addSelect = '') {
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$q = "SELECT $select\nFROM " . $this->quoteKey($table);
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE\n" . implode("\nAND ", $set);
		}
		$q .= "\n".$order;
		return $q;
	}

	function getSelectQuerySW($table, SQLWhere $where, $order = "", $addSelect = '') {
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ? $addSelect : $this->quoteKey($table1).".*";
		$q = "SELECT $select\nFROM " . $this->quoteKey($table);
		$q .= $where->__toString();
		$q .= "\n".$order;
		return $q;
	}

	function getDeleteQuery($table, $where = array(), $what = '') {
		$q = "DELETE ".$what." FROM $table ";
		$set = $this->quoteWhere($where);
		if (sizeof($set)) {
			$q .= "\nWHERE " . implode(" AND ", $set);
		} else {
			$q .= "\nWHERE 1 = 0"; // avoid truncate()
		}
		return $q;
	}

	function getDefaultInsertFields() {
		return array();
	}

	/**
	 * 2010/09/12: modified according to mantis request 0001812	- 4th argument added
	 */
	static function array_intersect($array, $field, $joiner = 'OR', $conditioner = 'ANY') {
		//$res[] = "(string_to_array('".implode(',', $value)."', ',')) <@ (string_to_array(bug.".$field.", ','))";
		// why didn't it work and is commented?

		//debug($array);
		if (sizeof($array)) {
			$or = array();
			foreach ($array as $langID) {
				//2010/09/12: modified according to mantis request 0001812	- if/else condition for 4th argument added
				if ($conditioner == 'ANY') {
					$or[] = "'" . $langID . "' = ANY(string_to_array(".$field.", ','))"; // this line is the original one
				} else {
					$or[] = "'" . $langID . "' = ".$field." ";
				}
			}
			$content = '('.implode(' '.$joiner.' ', $or).')';
		} else {
			$content = ' 1 = 1 ';
		}
		return $content;
	}

	function runSelectQuery($table, array $where = array(), $order = '', $addSelect = '') {
		$query = $this->getSelectQuery($table, $where, $order, $addSelect);
		//debug($query);
		$res = $this->db->perform($query);
		return $res;
	}

	function runSelectQuerySW($table, SQLWhere $where, $order = '', $addSelect = '') {
		$query = $this->getSelectQuerySW($table, $where, $order, $addSelect);
		//debug($query);
		$res = $this->db->perform($query);
		return $res;
	}

	/**
	 * Will search for $where and then either
	 * - update $fields + $where or
	 * - insert $fields + $where + $insert
	 * @param $table
	 * @param array $fields
	 * @param array $where
	 * @param array $insert
	 * @return bool|int
	 */
	function runInsertUpdateQuery($table, array $fields, array $where, array $insert = array()) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.'('.$table.')');
		$this->db->transaction();
		$res = $this->runSelectQuery($table, $where);
		if ($this->db->numRows($res)) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$inserted = 2;
		} else {
			$query = $this->getInsertQuery($table, $fields + $where + $insert);
			// array('ctime' => NULL) #TODO: make it manually now
			$inserted = TRUE;
		}
		//debug($query);
		$this->found = $this->db->fetchAssoc($res);
		$this->db->perform($query);
		$this->db->commit();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.'('.$table.')');
		return $inserted;
	}

	/**
	 * Inserts only if not yet found.
	 *
	 * @param $table
	 * @param array $fields
	 * @param array $insert
	 * @throws Exception
	 * @return resource
	 */
	function runInsertNew($table, array $fields, array $insert = array()) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$res = $this->runSelectQuery($table, $fields);
		if (!$this->db->numRows($res)) {
			$query = $this->getInsertQuery($table, $fields + $insert);
			//debug($query);
			$resInsert = $this->db->perform($query);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $resInsert;
	}

	function runInsertQuery($table, array $columns) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.'('.$table.')');
		$query = $this->getInsertQuery($table, $columns);
		$ret = $this->db->perform($query);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.'('.$table.')');
		return $ret;
	}

	function getFoundOrLastID($inserted) {
		if ($inserted) {
			$authorID = $this->db->lastInsertID($inserted);
		} else {
			$authorID = $this->found['id'];
		}
		return $authorID;
	}

	/**
	 * Return ALL rows
	 * @param string $table
	 * @param array $where
	 * @param string $order
	 * @param string $addFields
	 * @return array <type>
	 */
	function fetchSelectQuery($table, $where = array(), $order = '', $addFields = '') {
		// commented to allow working with multiple MySQL objects (SQLBuilder instance contains only one)
		//$res = $this->runSelectQuery($table, $where, $order, $addFields);
		$query = $this->getSelectQuery($table, $where, $order, $addFields);
		$res = $this->perform($query);
		$data = $this->fetchAll($res);
		return $data;
	}

	function runUpdateQuery($table, array $columns, array $where) {
		$query = $this->getUpdateQuery($table, $columns, $where);
		return $this->db->perform($query);
	}

	/**
	 * Originates from BBMM
	 * @param string $sword
	 * @param array $fields
	 * @return AsIs
	 */
	function getSearchWhere($sword, array $fields) {
		$where = array();
		$words = $this->getSplitWords($sword);
		foreach ($words as $word) {
			$like = array();
			foreach ($fields as $field) {
				$like[] = $field . " LIKE '%".mysql_real_escape_string($word)."%'";
			}
			$where[] = new AsIsOp(' ('.implode(' OR ', $like).')');
		}
		//debug($where);
		return $where;
	}

	function getSplitWords($sword) {
		$sword = trim($sword);
		$words = explode(' ', $sword);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	function combineSplitTags($words) {
		$new = array();
		$i = 0;
		foreach ($words as $word) {
			$word = new String($word);
			if ($word->contains('[')) {
				++$i;
				$in = true;
			}
			$new[$i] = $new[$i] ? $new[$i] . ' ' . $word : $word.'';
			if (!$in || ($in && $word->contains(']'))) {
				++$i;
				$in = false;
			}
		}
		//debug(array($words, $new));
		return $new;
	}

	function runDeleteQuery($table, array $where) {
		return $this->db->perform($this->getDeleteQuery($table, $where));
	}

	function __call($method, array $params) {
		return call_user_func_array(array($this->db, $method), $params);
	}

	function getTableOptions($table, $titleField, $where = array(), $order = NULL, $idField = NULL) {
		$res = $this->runSelectQuery($table, $where, $order,
			'DISTINCT '.$this->quoteKey($titleField).' AS title'.
			($idField ? ', '.$this->quoteKey($idField).' AS id_field' : ''),
			true);
		//debug($this->db->lastQuery, $this->db->numRows($res), $idField);
		if ($idField) {
			$data = $this->fetchAll($res, 'id_field');
		} else {
			$data = $this->fetchAll($res, 'title');
		}
		$keys = array_keys($data);
		$values = array_map(create_function('$arr', 'return $arr["title"];'), $data);
		//d($keys, $values);
		if ($keys && $values) {
			$options = array_combine($keys, $values);
		} else {
			$options = array();
		}
		//		$options = AP($data)->column_assoc($idField, $titleField)->getData();
		return $options;
	}

	/**
	 * @param resource|string $res
	 * @param string $key can be set to NULL to avoid assoc array
	 * @return array
	 */
	function fetchAll($res, $key = NULL) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (is_string($res)) {
			$res = $this->perform($res);
		}

		$data = array();
		do {
			$row = $this->fetchAssoc($res);
			if ($row === FALSE || $row == array()) {
				break;
			}
			if ($key) {
				$data[$row[$key]] = $row;
			} else {
				$data[] = $row;
			}
		} while (true);
		//debug($this->lastQuery, sizeof($data));
		//debug_pre_print_backtrace();
		$this->free($res);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $data;
	}

	/**
	 * @var string $query
	 * @return resource
	 */
	function getIterator($query) {
		if ($this->db instanceof dbLayerPDO) {
			$res = $this->db->perform($query);
			return $res;
		} else {
			$di = new DIContainer();
			$di->db = $this->db;
			$f = new DatabaseResultIteratorAssoc($di);
			$f->perform($query);
			return $f;
		}
	}

	function fetchOneSelectQuery($table, $where = array(), $order = '', $selectPlus = '') {
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		$res = $this->perform($query);
		$data = $this->fetchAssoc($res);
		return $data;
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

	/**
	 * @param string $table
	 * @param array $where
	 * @param string $order
	 * @param string $selectPlus
	 * @param $key
	 * @return array[]
	 */
	function fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = NULL) {
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		$rows = $this->db->fetchAll($res, $key);
		return $rows;
	}

}
