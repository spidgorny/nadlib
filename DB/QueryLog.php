<?php

class QueryLog {

	var $queryLog = array();

	function log($query, $diffTime) {
		$key = md5($query);
		$this->queryLog[$key] = is_array($this->queryLog[$key]) ? $this->queryLog[$key] : array();
		$this->queryLog[$key]['query'] = $query;
		$this->queryLog[$key]['sumtime'] += $diffTime;
		$this->queryLog[$key]['times']++;
	}

	function dumpQueries() {

	}

	/**
	 * Renders the list of queries accumulated
	 * @return string
	 */
	function dumpQueriesTP() {
		$q = $this->QUERIES;
		arsort($q);
		foreach ($q as $query => &$time) {
			$times = $this->QUERYMAL[$query];
			$time = array(
				'times' => $times,
				'query' => $query,
				'time' => number_format($time, 3),
				'time/1' => number_format($time/$times, 3),
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
		$content = '<div class="profiler">'.$q.'</div>';
		return $content;
	}

	function getDBTime() {
		$sumtime = ArrayPlus::create($this->queryLog)->column('sumtime')->sum();
		return $sumtime;
	}

}
