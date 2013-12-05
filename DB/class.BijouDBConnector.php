<?php

class BijouDBConnector {

	/**
	 * @var t3lib_DB
	 */
	protected $t3db;
	
	/**
	 * @var string
	 */
	var $lastQuery;

	function __construct(t3lib_DB $t3lib_DB = NULL) {
		$this->t3db = $t3lib_DB ?: $GLOBALS['TYPO3_DB'];
	}
	
	function perform($query) {
		$this->lastQuery = $query;
		$start = array_sum(explode(' ', microtime()));
		$res = $this->t3db->sql_query($query);
		$elapsed = array_sum(explode(' ', microtime())) - $start;
		$this->saveQueryLog($query, $elapsed);
		return $res;
	}

	/**
	 * @see SQLBuilder
	 * @param $res
	 * @return mixed
	 */
	/*	function getTableOptions($table, $titleField, $where = array(), $order = NULL, $idField = 'uid', $noDeleted = FALSE) {
			//$query = $this->getSelectQuery($table, $where, $order);
			$res = $this->runSelectQuery($table, $where, $order, '', FALSE, !$noDeleted);
			//t3lib_div::debug($where);
			//t3lib_div::debug($query);
			//$res = $this->perform($query);
			$data = $this->fetchAll($res);
			$options = $this->IDalize($data, $titleField, $idField);
			//debugster($options);
			return $options;
		}
	*/
	function fetchAssoc($res) {
		if (is_string($res)) {
			$res = $this->perform($res);
		}
		$row = $this->t3db->sql_fetch_assoc($res);
		//d($res, $row);
		return $row;
	}

	function fetchRow($res) {
		return $this->t3db->sql_fetch_row($res);
	}

	function fetchAll($res, $key = 'uid') {
		$data = array();
		while (($row = $this->fetchAssoc($res)) !== FALSE) {
			$data[$row[$key]] = $row;
		}
		return $data;
	}

	function fetchAllAsIs($res) {
		$data = array();
		while (($row = $this->fetchAssoc($res)) !== FALSE) {
			$data[] = $row;
		}
		return $data;
	}

	function fetchAllFromJoin($res, $prefixes) {
		$data = array();
		while (($row = mysql_fetch_row($res)) !== FALSE) {
			$prow = $this->distributePrefixes($res, $row, $prefixes);
			$data[] = $prow;
		}
		return $data;
	}

	function distributePrefixes($res, $row, $prefixes) {
		$prow = array();
		reset($prefixes);
		$coli = 0;
		foreach ($row as $colv) {
			$name = mysql_field_name($res, $coli);
			if ($name == 'uid' && $coli > 0) {
				next($prefixes);
			}
			$prefix = current($prefixes);
			$prow[$prefix.'.'.$name] = $colv;

			$coli++;
		}
		return $prow;
	}

	function getLastInsertID($res = NULL) {
		return $this->t3db->sql_insert_id($res);
	}

	function quoteSQL($value, $desc) {
		//var_dump($value); print(gettype($value) . "<br>");
		if ($value === NULL) {
			return 'NULL';
		} else if ($value === TRUE) {
			return "TRUE";
		} else if ($value === FALSE) {
			return "FALSE";
		} else if (is_int($value)) {
			return $value;
		} else if ($desc['asis']) {
			return /*$this->escapeString(*/$value/*)*/;
		} else {
			return $this->escapeString($value);
		}
	}

	function numRows($res) {
		return $this->t3db->sql_num_rows($res);
	}

	function escapeString($value) {
		return $this->t3d->fullQuoteStr($value, '');
	}

	function getDefaultInsertFields() {
		$set = array(
			'pid' => $this->caller->generalStoragePID,
			'crdate' => time(),
			'tstamp' => time(),
			'cruser_id' => $GLOBALS['TSFE']->fe_user->user['uid'] ? $GLOBALS['TSFE']->fe_user->user['uid'] : 0,
		);
		//debugster($set);
		return $set;
	}

	function transaction() {
		$this->t3db->sql_query('BEGIN');
	}

	function commit() {
		$this->t3db->sql_query('COMMIT');
	}

	function rollback() {
		$this->t3db->sql_query('ROLLBACK');
	}

	function lockTables($table) {
		$this->t3db->sql_query('LOCK TABLES '.$table);
	}

	function unlockTables() {
		$this->t3db->sql_query('UNLOCK TABLES');
	}

	function saveQueryLog() {}

	/**
	 * Returns THE ONE FIRST result.
	 *
	 * @param string $table
	 * @param array $where
	 * @param string $orderBy
	 * @param string $what
	 * @param bool $whatExclusive
	 * @return array
	 */
	function fetchSelectQuery($table, $where = array(), $orderBy = '', $what = '', $whatExclusive = false) {
		$result = $this->runSelectQuery($table, $where, $orderBy, $what, $whatExclusive);
/*		if ($this->numRows($result)) {
			$row = $this->fetchAssoc($result);
			return $row;
		} else {
			return NULL;
		}
*/
		$row = $this->fetchAssoc($result);
		return $row;
	}

	function runSelectQuery($table, array $where = array(), $orderBy = '', $what = '', $whatExclusive = FALSE, $filterFields = TRUE) {
		if ($filterFields) {
			//$where += $this->filterFields(NULL, NULL, $this->getFirstWord($table));
		}
		$qb = Config::getInstance()->qb;
		$query = $qb->getSelectQuery($table, $where, $orderBy, $what, $whatExclusive);
		//debug($query);
		$result = $this->perform($query);
		return $result;
	}

	function escape($str) {
		return $this->t3db->quoteStr($str, '');
	}

	function quoteKey($key) {
		return MySQL::quoteKey($key);
	}

	function getTableColumns($table) {
		return $this->t3db->admin_get_fields($table);
	}

	function dataSeek($res, $i) {
		return $this->t3db->sql_data_seek($res, $i);
	}

}
