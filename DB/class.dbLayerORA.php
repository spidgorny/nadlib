<?php

/**
 * Class dbLayerORA is completely deprecated.
 * There's not even a documentation on the php.net.
 */
class dbLayerORA extends dbLayer
{
	var $CONNECTION = NULL;
	var $COUNTQUERIES = 0;
	var $LAST_PERFORM_RESULT;
	var $LOG;
	var $debug = FALSE;
	var $debugOnce = FALSE;

	function dbLayerORA($tns, $pass)
	{
		$this->connect($tns, '', $pass);
	}

	function connect($tns, $user, $pass, $host = 'localhost')
	{
		$this->CONNECTION = ora_logon($tns, $pass);
		ora_commiton($this->CONNECTION);
		return $this->CONNECTION;
	}

	function getConnection()
	{
		return $this->CONNECTION;
	}

	function disconnect()
	{
		ora_logoff($this->CONNECTION);
	}

	function perform($query, $canprint = TRUE)
	{
		$this->COUNTQUERIES++;
		if ($this->debugOnce || $this->debug) {
			//debug($query);
		}

		list($time1['usec'], $time1['sec']) = explode(" ", microtime());
		$time1['float'] = (float)$time1['usec'] + (float)$time1['sec'];

		$this->LAST_PERFORM_RESULT = ora_open($this->CONNECTION);
		ora_parse($cursor, $query, TRUE) or $canprint ? my_print_backtrace($query) : '';
		ora_exec($this->LAST_PERFORM_RESULT);

		list($time2['usec'], $time2['sec']) = explode(" ", microtime());
		$time2['float'] = (float)$time2['usec'] + (float)$time2['sec'];

		$numRows = $this->numRows($this->LAST_PERFORM_RESULT);
		if ($this->debugOnce || $this->debug) {
			debug(array($query, $numRows));
			$this->debugOnce = FALSE;
		}
		$elapsed = number_format($time2['float'] - $time1['float'], 3);
		$debug = debug_backtrace();
		foreach ($debug as $i => $row) {
			if ($i > 1) {
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

	function done($result)
	{
		ora_close($result);
	}

	function transaction($serializable = false)
	{
		// everything is a transaction in oracle
		ora_commitoff($this->CONNECTION);
	}

	function commit()
	{
		ora_commit($this->CONNECTION);
		ora_commiton($this->CONNECTION);
	}

	function rollback()
	{
		ora_rollback($this->CONNECTION);
	}

	function quoteSQL($value)
	{
		if ($value == "CURRENT_TIMESTAMP") {
			return $value;
		} else if ($value === NULL) {
			return 'NULL';
		} else if ($value === TRUE) {
			return TRUE;
		} else if ($value === FALSE) {
			return "'f'";
		} else if (is_numeric($value)) {
			return $value;
		} else {
			return "'" . pg_escape_string($value) . "'";
		}
	}

	function fetchAssoc($result)
	{
		$res = ora_fetch_into($result, $array, ORA_FETCHINTO_NULLS | ORA_FETCHINTO_ASSOC);
		if ($res) {
			return $array;
		} else {
			return FALSE;
		}
	}

	function numRows($result)
	{
		return ora_numrows($result);
	}

}
