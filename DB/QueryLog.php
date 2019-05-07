<?php

class QueryLog
{

	/**
	 * @var array[
	 * 'query'
	 * 'sumtime',
	 * 'times'
	 * 'results'
	 * ]
	 */
	var $queryLog = array();

	public function log($query, $diffTime, $results = NULL)
	{
		$key = md5(trim($query));
//		debug(__METHOD__, $query, $diffTime, $key, array_keys($this->queryLog));
		if (isset($this->queryLog[$key])) {
			$old = $this->queryLog[$key];
		} else {
			$old = array();
		}
		$this->queryLog[$key] = array(
			'query' => $query,
			'sumtime' => ifsetor($old['sumtime']) + $diffTime,
			'times' => ifsetor($old['times']) + 1,
			'results' => $results,
		);
//		debug($key, $this->queryLog);
	}

	/**
	 * Renders the list of queries accumulated
	 * @return string
	 */
	function dumpQueries()
	{
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
				'time/1' => number_format($time / $times, 3),
				//'func' => $this->QUERYFUNC[$query],
			);
		}
		$q = new slTable($q, 'class="view_array table" width="1024"', array(
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
		$content = '<div class="profiler">' . $q . '</div>';
		return $content;
	}

	function getDBTime()
	{
		$sumtime = ArrayPlus::create($this->queryLog)->column('sumtime')->sum();
		//debug(sizeof($this->queryLog), $sumtime);
		return $sumtime;
	}

	function dumpQueriesBijou(array $log, $totalTime)
	{
		//debug(trim(first($log)['query']));
		foreach ($log as &$row) {
			$sQuery = trim(strip_tags($row['query']));
			if (str_startsWith($sQuery, /** @lang text */
				"UPDATE")) {
				$row['query'] = 'UPDATE ...';
			} else {
				$row['query'] = substr($row['query'], 0, 100);
			}
			if ($row['results'] >= 1000) {
				$row['results'] = new htmlString('<font color="red">' . $row['results'] . '</font>');
			}
			if ($row['count'] >= 3) {
				$row['count'] = new htmlString('<font color="red">' . $row['count'] . '</font>');
			}
		}
		$s = new slTable(NULL, 'width="100%" class="table"');
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

	function dumpQueriesTP()
	{
		$queryLog = ArrayPlus::create($this->queryLog);
		//debug($queryLog);
		$sumTimeCol = $queryLog->column('sumtime');
		$sumTime = $sumTimeCol->sum();
		$queryLog->sortBy('sumtime')->reverse();
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		//debug($queryLog->getData()); exit();
		$log = array();
		foreach ($queryLog as $set) {
			$query = $set['query'];
			$time = ifsetor($set['time'], $set['sumtime'] / $set['times']);
			$log[] = array(
				'times' => $set['times'],
				'query' => '<small>' . htmlspecialchars($query) . '</small>',
				'sumtime' => number_format($set['sumtime'], 3, '.', '') . 's',
				'time' => number_format($time, 3, '.', '') . 's',
				'%' => $sumTime != 0 ? $pb->getImage($set['sumtime'] / $sumTime * 100) : '',
				'results' => $set['results'],
			);
		}
		$s = new slTable($log, 'class="table"', array(
			'times' => 'times',
			'sumtime' => array(
				'name' => 'sumtime (' . number_format($sumTime, 3) . ')',
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
			'query' => array(
				'name' => 'query',
				'no_hsc' => true,
			),
			'results' => array(
				'name' => 'Results',
			)
		));
		return $s;
	}

}
