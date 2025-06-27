<?php

class QueryLog
{

	/**
	 * @var array
	 * ['query'
	 * 'sumtime',
	 * 'times'
	 * 'results'
	 * ]
	 */
	public $queryLog = [];

	public function log(string $query, $diffTime, ?int $results = null, $ok = null): void
	{
		$key = md5(trim($query));
		//		debug(__METHOD__, $query, $diffTime, $key, array_keys($this->queryLog));
		$old = $this->queryLog[$key] ?? [];

		$this->queryLog[$key] = [
			'query' => $query . '',
			'sumtime' => ifsetor($old['sumtime']) + $diffTime,
			'times' => ifsetor($old['times']) + 1,
			'results' => $results,
			'ok' => $ok instanceof PgSql\Result ? get_class($ok) : $ok,
		];
//		debug($key, $this->queryLog);
	}

	/**
	 * Renders the list of queries accumulated
	 */
	public function dumpQueries(): string
	{
		$q = $this->queryLog;
		arsort($q);
		foreach ($q as &$row) {
			$query = $row['query'];
			$time = $row['sumtime'];
			$times = $row['times'];
			$row = [
				'times' => $times,
				'query' => $query,
				'time' => number_format($time, 3),
				'time/1' => number_format($time / $times, 3),
				//'func' => $this->QUERYFUNC[$query],
			];
		}

		$q = new slTable($q, ['class' => "view_array table", 'width' => "1024"], [
			'times' => 'Times',
			'time' => [
				'name' => 'Time',
				'align' => 'right',
			],
			'time/1' => [
				'name' => 'Time/1',
				'align' => 'right',
			],
			'query' => 'Query',
			'func' => 'Caller',
		]);
		$q->isOddEven = false;

		return '<div class="profiler">' . $q . '</div>';
	}

	public function getDBTime(): float|int
	{
		//debug(sizeof($this->queryLog), $sumtime);
		return ArrayPlus::create($this->queryLog)->column('sumtime')->sum();
	}

	public function dumpQueriesBijou(array $log, $totalTime)
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
				$row['results'] = new HtmlString('<font color="red">' . $row['results'] . '</font>');
			}

			if ($row['count'] >= 3) {
				$row['count'] = new HtmlString('<font color="red">' . $row['count'] . '</font>');
			}
		}

		$s = new slTable(null, ['width' => "100%", 'class' => "table"]);
		$s->thes([
			'query' => [
				'label' => 'Query',
				'no_hsc' => true,
				'wrap' => new Wrap('<small>|</small>'),
			],
			'function' => 'Function',
			'line' => 'Line',
			'results' => 'Rows',
			'elapsed' => 'Elapsed',
			'count' => 'Count',
			'total' => $totalTime,
			'percent' => '100%']);
		$s->data = $log;
		$s->isOddEven = true;
		$s->more = ['class' => "nospacing"];

		return $s->getContent();
	}

	public function dumpQueriesTP(): \slTable
	{
		$queryLog = ArrayPlus::create($this->queryLog);
		//debug($queryLog);
		$sumTimeCol = $queryLog->column('sumtime');
		$sumTime = $sumTimeCol->sum();
		$queryLog->sortBy('sumtime')->reverse();
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		//debug($queryLog->getData()); exit();
		$log = [];
		foreach ($queryLog as $set) {
			$query = $set['query'];
			$time = ifsetor($set['time'], $set['sumtime'] / $set['times']);
			$log[] = [
				'times' => $set['times'],
				'query' => '<small>' . htmlspecialchars($query) . '</small>',
				'sumtime' => number_format($set['sumtime'], 3, '.', '') . 's',
				'time' => number_format($time, 3, '.', '') . 's',
				'%' => $sumTime != 0 ? $pb->getImage($set['sumtime'] / $sumTime * 100) : '',
				'results' => $set['results'],
			];
		}

		return new slTable($log, ['class' => "table"], [
			'times' => 'times',
			'sumtime' => [
				'name' => 'sumtime (' . number_format($sumTime, 3) . ')',
				'align' => 'right',
			],
			'time' => [
				'name' => 'time',
				'align' => 'right',
			],
			'%' => [
				'name' => '%',
				'align' => 'right',
				'no_hsc' => true,
			],
			'query' => [
				'name' => 'query',
				'no_hsc' => true,
			],
			'results' => [
				'name' => 'Results',
			]
		]);
	}

}
