<?php

class Flot extends AppController {

	protected $colors = array(
		'#edc240',
		'#afd8f8',
		'#cb4b4b',
		'#edc240',
		'#afd8f8',
		'#cb4b4b',
		"#9440ed",
		"#40ed94",
		'#4da74d',
	);

	protected $data, $cumulative;
	public $movingAverage;

	protected $keyKey, $timeKey, $amountKey;

	public $MALength = 20;

	function __construct(array $data, $keyKey, $timeKey, $amountKey) {
		$this->data = $data;
		$this->keyKey = $keyKey;
		$this->timeKey = $timeKey;
		$this->amountKey = $amountKey;
	}

	function render() {
		$content = '';
		$charts = $this->getChartTable($this->data);
		$content .= $this->showChart('chart1', $charts);
		return $content;
	}

	function renderCumulative() {
		$content = '';
		$charts = $this->getChartTable($this->data);
		$this->cumulative = $this->getChartCumulative($charts);
		$content .= $this->showChart('chart1', $charts, $this->cumulative);
		return $content;
	}

	/**
	 * http://bytes.com/topic/php/answers/747586-calculate-moving-average
	 * @return string
	 */
	function renderMovingAverage() {
		$content = '';
		$charts = $this->getChartTable($this->data);
		$this->movingAverage = $this->getMovingAverage($charts);
		$content .= $this->showChart('chart1', $charts, $this->movingAverage);
		return $content;
	}

	function __toString() {
		return $this->render();
	}

	function appendCumulative(array $data) {
		$cumulative = $this->cumulative;
		foreach ($cumulative as $i => $series) {
			$cumulative = array_merge($cumulative, $series);
			unset($cumulative[$i]);
		}
		$cumulative = array_values($cumulative);
		$dataClass = array();
		foreach ($data as $i => &$row) {
			$color = $this->colors[$row[$this->keyKey]-1];
			$dataClass[$i] = '" style="background: white; color: '.$color;
			$row['###TD_CLASS###'] = '" style="background: white; color: '.$color;

			$row['cumulative'] = $cumulative[$i][1];
		}
		return $data;
	}

	/**
	 * Return a multitude of rows which are extracted by the $keyKey.
	 * Each row is an assoc array with $timeKey keys and $amountKey values.
	 *
	 * @param array $rows
	 * @internal param string $keyKey
	 * @internal param string $timeKey
	 * @internal param string $amountKey
	 * @return array
	 */
	function getChartTable(array $rows) {
		$chart = array();
		foreach ($rows as $i => $row) {
			$key = $row[$this->keyKey];
			$time = $row[$this->timeKey];
			if ($time) {
				$time = strtotime($time);
				$chart[$key][$time] = array($time*1000, $row[$this->amountKey]);
			} else {
				unset($rows[$i]);
			}
		}
		//debug(__METHOD__, $chart);
		return $chart;
	}

	function getChartCumulative(array $charts) {
		foreach ($charts as &$sub) {
			$sum = 0;
			foreach ($sub as &$pair) {
				$sum += $pair[1];
				$pair[1] = $sum;
			}
		}
		return $charts;
	}

	function getChartMax(array $charts) {
		$max = 0;
		foreach ($charts as $series) {
			foreach ($series as $pair) {
				$max = max($max, $pair[1]);
			}
		}
		return $max;
	}

	function getMovingAverage(array $charts) {
		foreach ($charts as $s => &$series) {
			$res = array();
			foreach ($series as $pair) {
				$res[$pair[0]] = $pair[1];
			}

			$i = 0;
			foreach ($res as &$row) {
				$slice = array_slice($res, max($i-$this->MALength+1, 0), $this->MALength);
				$row = round(
					array_sum($slice) /
					count($slice), 4);
				$i++;
			}

			foreach ($res as $key => &$val) {
				$val = array($key, $val);
			}
			$series = $res;
		}
		return $charts;
	}

	function showChart($divID, array $charts, array $cumulative = array()) {
		$max = $this->getChartMax($charts);
		Index::getInstance()->addJQuery();
		Index::getInstance()->footer['flot'] = '
		<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
    	<script language="javascript" type="text/javascript" src="vendor/flot/flot/jquery.flot.js"></script>
    	<script language="javascript" type="text/javascript" src="vendor/flot/flot/jquery.flot.time.js"></script>';

		$content = '<div id="'.$divID.'" style="width: 950px; height:600px; border: solid 1px silver;"></div>';

		foreach ($charts as $key => &$rows) {
			$array = $rows ? array_values($rows) : array();
			$rows = 'var d'.$key.' = {
				label: "'.$key.'",
				data: '.json_encode($array).',
				bars: {
					show: true,
					barWidth: 24*60*60*1000*0.75,
					align: "center"
				}
			};';
		}

		if ($cumulative) {
			foreach ($cumulative as $key => &$rows) {
				$array = $rows ? array_values($rows) : array();
				$rows = 'var c'.$key.' = {
					data: '.json_encode($array).',
					lines: {
						show: true,
						fill: false
					},
					yaxis: 2
				};';
			}
		} else {
			$cumulative = array('Daily' => 'var cDaily = {};');
		}
		//$max *= 2;

		Index::getInstance()->footer[$divID] = '
    	<script type="text/javascript">
$(function () {
	'.implode("\n", $charts).'
	'.implode("\n", $cumulative).'
    $.plot($("#'.$divID.'"), [
    	d'.implode(", d", array_keys($charts)).',
    	c'.implode(", c", array_keys($cumulative)).'
    ], {
    	xaxis: {
    		mode: "time"
    	},
    	yaxes: [ {
    			max: '.$max.'
    		}, {
    			position: "right"
    		}
    	],
    	colors: '.json_encode($this->colors).'
    });
});
</script>';
		return $content;
	}

}
