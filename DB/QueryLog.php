<?php

class QueryLog {

	var $queryLog = array();

	public function log($query, $diffTime) {
		$key = md5($query);
		$this->queryLog[$key] = is_array(ifsetor($this->queryLog[$key]))
			? $this->queryLog[$key] : array();
		$this->queryLog[$key]['query'] = $query;
		$this->queryLog[$key]['sumtime'] = ifsetor($this->queryLog[$key]['sumtime']) + $diffTime;
		$this->queryLog[$key]['times'] = ifsetor($this->queryLog[$key]['times']) + 1;
	}

	/**
	 * Renders the list of queries accumulated
	 * @return string
	 */
	function dumpQueries() {
		$q = $this->queryLog;
		arsort($q);
		foreach ($q as &$row) {
			$query = $row['query'];
			$time = $row['sumtime'];
			$times = $row['times'];
			$row = array(
				'times' => $times,
				'query' => $query,
				'time' => number_format($time, 3),
				'time/1' => number_format($time/$times, 3),
				//'func' => $this->QUERYFUNC[$query],
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

	function dumpQueriesBijou(array $log, $totalTime) {
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

	function dumpQueriesTP() {
		$queryLog = ArrayPlus::create($this->queryLog);
		//debug($queryLog);
		array_multisort($queryLog->column('sumtime')->getData(), SORT_DESC, $queryLog);
		$log = array();
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$sumTime = $queryLog->column('sumtime')->sum();
		foreach ($queryLog as $set) {
			$query = $set['query'];
			$time = $set['time'];
			$log[] = array(
					'times' => $set['times'],
					'query' => '<small>'.htmlspecialchars($query).'</small>',
					'sumtime' => number_format($set['sumtime'], 3, '.', '').'s',
					'time' => number_format($time, 3, '.', '').'s',
					'%' => $pb->getImage($set['sumtime']/$sumTime*100),
			);
		}
		$s = new slTable($log, '', array(
				'times' => 'times',
				'query' => array(
						'name' => 'query',
						'no_hsc' => true,
				),
				'sumtime' => array(
						'name' => 'sumtime ('.number_format($sumTime, 3).')',
						'align' => 'right',
				),
				'time' => array(
						'name' => 'time',
						'align' => 'right',
				),
				'%' => array(
						'name' => '%',
						'align' => 'right',
						'no_hsc' => true,
				),
		));
		return $s;
	}

}
