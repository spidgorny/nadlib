<?php

/**
 * Class dbLayerORA is completely deprecated.
 * There's not even a documentation on the php.net.
 */
class DBLayerORA extends DBLayer implements DBInterface
{
	public $connection = null;
	public $COUNTQUERIES = 0;
	public $LAST_PERFORM_RESULT;
	public $LOG;
	public $debug = false;
	public $debugOnce = false;

	public function __construct($tns, $pass)
	{
		$this->connect($tns, '', $pass);
	}

	public function connect($tns = null, $user = null, $pass = null, $host = 'localhost')
	{
		$this->connection = ora_logon($tns, $pass);
		ora_commiton($this->connection);
		return $this->connection;
	}

	public function getConnection()
	{
		return $this->connection;
	}

	public function disconnect()
	{
		ora_logoff($this->connection);
	}

	public function performORA($query, $canprint = true)
	{
		$this->COUNTQUERIES++;
		if ($this->debugOnce || $this->debug) {
			//debug($query);
		}

		list($time1['usec'], $time1['sec']) = explode(" ", microtime());
		$time1['float'] = (float)$time1['usec'] + (float)$time1['sec'];

		$cursor = null;
		$this->LAST_PERFORM_RESULT = ora_open($this->connection);
		ora_parse($cursor, $query, true) or $canprint ? debug($query) : '';
		ora_exec($this->LAST_PERFORM_RESULT);

		list($time2['usec'], $time2['sec']) = explode(" ", microtime());
		$time2['float'] = (float)$time2['usec'] + (float)$time2['sec'];

		$numRows = $this->numRows($this->LAST_PERFORM_RESULT);
		if ($this->debugOnce || $this->debug) {
			debug([$query, $numRows]);
			$this->debugOnce = false;
		}
		$elapsed = number_format($time2['float'] - $time1['float'], 3);
		$debug = debug_backtrace();
		$deb = '';
		foreach ($debug as $i => $row) {
			if ($i > 1) {
				$deb .= implode(', ', $row);
				$deb .= "\n";
			}
		}
		if ($this->LOG[$query]) {
			$a = $this->LOG[$query];
			$this->LOG[$query] = [
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
			];
		} else {
			$this->LOG[$query] = [
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
			];
		}
		return $this->LAST_PERFORM_RESULT;
	}

	public function done($result)
	{
		ora_close($result);
	}

	public function transaction($serializable = false)
	{
		// everything is a transaction in oracle
		ora_commitoff($this->connection);
	}

	public function commit()
	{
		ora_commit($this->connection);
		ora_commiton($this->connection);
	}

	public function rollback()
	{
		ora_rollback($this->connection);
	}

	public function quoteSQL($value, $key = null)
	{
		if ($value == "CURRENT_TIMESTAMP") {
			return $value;
		} elseif ($value === null) {
			return 'NULL';
		} elseif ($value === true) {
			return true;
		} elseif ($value === false) {
			return "'f'";
		} elseif (is_numeric($value)) {
			return $value;
		} else {
			return "'" . pg_escape_string($value) . "'";
		}
	}

	public function fetchAll($result, $key = null)
	{
		$ret = [];
		while (($row = $this->fetchAssoc($result)) !== false) {
			$ret[] = $row;
		}
		return $ret;
	}

	public function fetchAssoc($result)
	{
		$array = [];
		$res = ora_fetch_into($result, $array, ORA_FETCHINTO_NULLS | ORA_FETCHINTO_ASSOC);
		if ($res) {
			return $array;
		} else {
			return false;
		}
	}

	public function numRows($result = null)
	{
		return ora_numrows($result);
	}

}
