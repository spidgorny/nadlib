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

	/**
	 * Renders the list of queries accumulated
	 * @return string
	 */
	function dumpQueries() {
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

	function dumpQueriesBijou($log, $totalTime) {
		foreach ($log as &$row) {
			if (str::beginsWith($row['query'], 'UPDATE preference SET value')) {
				$row['query'] = 'UPDATE preferences...';
			}
			if ($row['results'] >= 1000) {
				$row['results'] = new htmlString('<font color="red">'.$row['results'].'</font>');
			}
			if ($row['count'] >= 3) {
				$row['count'] = new htmlString('<font color="red">'.$row['count'].'</font>');
			}
		}
		$s = new slTable(NULL, 'width="100%"');
		$s->thes(array(
				'query' => array(
						'label' => 'Query',
						'no_hsc' => true,
						'wrap' => new Wrap('<small>|</small>'),
				),
				'function' => 'Function',
				'line' => 'Line',
				'results' => 'Rows',
				'elapsed' => 'Elapsed',
				'count' => 'Count',
				'total' => $totalTime,
				'percent' => '100%'));
		$s->data = $log;
		$s->isOddEven = TRUE;
		$s->more = 'class="nospacing"';
		$content = $s->getContent();
		return $content;
	}

}
