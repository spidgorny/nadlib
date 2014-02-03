<?php

class dbLayerOCI extends dbLayer {
	var $CONNECTION = NULL;
	var $COUNTQUERIES = 0;
	var $LAST_PERFORM_RESULT;
	var $LOG;
	var $debug = FALSE;
	var $debugOnce = FALSE;
	var $is_connected = FALSE;

	function dbLayerOCI($user, $pass, $tns) {
		$this->connect($user, $pass, $tns);
		//debug('<div class="error">OCI CONNECT</div>');
	}

	function __toString() {
		return '[Object of type dbLayerOCI]';
	}

	function connect($user, $pass, $tns) {
		$this->CONNECTION = oci_connect($user, $pass, $tns);
		if (!$this->CONNECTION) {
			print('Error in Oracle library: connection failed. Reason: '.getDebug(oci_error($this->CONNECTION)).BR);
			return NULL;
		}
		return $this->CONNECTION;
	}

	function getConnection() {
		return $this->CONNECTION;
	}

	function disconnect() {
		oci_close($this->CONNECTION);
	}

	function insertFields() {
		return array();
	}

	function updateFields() {
		return array();
	}

	function perform($query, $canprint = TRUE, $try = FALSE) {
		if (!$this->CONNECTION) {
			print('Error in Oracle library: no connection. Query: '.$query.BR);
			return NULL;
		}
		$this->COUNTQUERIES++;
		if ($this->debugOnce || $this->debug) {
			//debug($query);
		}

		list($time1['usec'], $time1['sec']) = explode(" ", microtime());
		$time1['float'] = (float)$time1['usec'] + (float)$time1['sec'];

		$this->LAST_PERFORM_RESULT = oci_parse($this->CONNECTION, $query);
		$error = oci_error();
		if ($error) {
			print('Oracle error '.$error['code'].': '.$error['message'].' while doing '.$query.BR);
		}
		if ($try) {
			@oci_execute($this->LAST_PERFORM_RESULT, OCI_DEFAULT);
			//debug($this->LAST_PERFORM_RESULT); exit();
			//debug(oci_error($this->LAST_PERFORM_RESULT)); exit();
			if (oci_error($this->LAST_PERFORM_RESULT)) {
				$this->LAST_PERFORM_RESULT = NULL;
			}
		} else {
			oci_execute($this->LAST_PERFORM_RESULT, OCI_DEFAULT);
			$error = oci_error($this->LAST_PERFORM_RESULT);
			if ($error) {
				print('Oracle error '.$error['code'].': '.$error['message'].' while doing '.$query.BR);
			}
		}

		list($time2['usec'], $time2['sec']) = explode(" ", microtime());
		$time2['float'] = (float)$time2['usec'] + (float)$time2['sec'];

		//$numRows = $this->numRows($this->LAST_PERFORM_RESULT);
		if ($this->debugOnce || $this->debug) {
			debug(array($query));
			$this->debugOnce = FALSE;
		}
		$elapsed = number_format($time2['float'] - $time1['float'], 3);
		$debug = debug_backtrace();
		foreach ($debug as $i => $row) {
			if ($i > 1) {
				unset($row['object']);
				$deb .= implode(', ', $row);
				$deb .= "\n";
			}
		}
		if ($this->LOG[$query]) {
			$a = $this->LOG[$query];
			$this->LOG[$query] = array(
				'timestamp' => '',
				'file' => $debug[1]['file'],
				'function' => $debug[1]['function'],
				'line' => $debug[1]['line'],
				'title' => $deb,
				'query' => $query,
				'results' => $numRows,
				'elapsed' => $a['elapsed'],
				'count' => 1 + $a['count'],
				'total' => $elapsed + $a['total'],
			);
		} else {
			$this->LOG[$query] = array(
				'timestamp' => date('H:i:s'),
				'file' => $debug[1]['file'],
				'function' => $debug[1]['function'],
				'line' => $debug[1]['line'],
				'title' => $deb,
				'query' => $query,
				'results' => $numRows,
				'elapsed' => $elapsed,
				'count' => 1,
				'total' => $elapsed,
			);
		}
		return $this->LAST_PERFORM_RESULT;
	}

	function done($result) {
		oci_free_statement($result);
	}

/*	function transaction() {
		// everything is a transaction in oracle
		ora_commitoff($this->CONNECTION);
	}
*/
	function commit() {
		ocicommit($this->CONNECTION);
	}
/*
	function rollback() {
		ora_rollback($this->CONNECTION);
	}
*/
	function quoteSQL($value, $more = array()) {
		if ($value == "CURRENT_TIMESTAMP") {
			return $value;
		} else if ($value === NULL) {
			return 'NULL';
		} else if ($value === TRUE) {
			return "'t'";
		} else if ($value === FALSE) {
			return "'f'";
		} else if ($more['asis']) {
			return $value;
		} else if (is_numeric($value)) {
			return $value;
		} else if ($value == '') {
			return "NULL";
		} else {
			return "'".pg_escape_string($value)."'";
		}
	}

	function fetchAll($result) {
		$ret = array();
		while (($row = $this->fetchAssoc($result)) !== FALSE) {
			$ret[] = $row;
		}
		return $ret;
	}

	function fetchAssoc($result) {
		$array = oci_fetch_array($result, OCI_RETURN_NULLS | OCI_ASSOC);
		return $array;
	}

	function numRowsFast($result) {
		return oci_num_rows($result);
	}

	function numRows($result) {
		$i = 0;
		while (($row = $this->fetchAssoc($result)) !== FALSE) {
			$i++;
		}
		return $i;
	}

	function to_date($timestamp) {
		$content .= "to_date('".date('Y-m-d H:i', $timestamp)."', 'yyyy-mm-dd hh24:mi')";
		return $content;
	}

	function to_timestamp($value) {
		return strtotime($value);
	}

	function filterFields() {
		return array();
	}

};

?>