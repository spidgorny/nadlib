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

	protected $keyKey, $timeKey, $amountKey;

	function __construct(array $data, $keyKey, $timeKey, $amountKey) {
		$this->data = $data;
		$this->keyKey = $keyKey;
		$this->timeKey = $timeKey;
		$this->amountKey = $amountKey;
	}

	function render() {
		$content = '';
		$chart = $this->getChartTable($this->data);
		$this->cumulative = $this->getChartCumulative($chart);
		$max = $this->getChartMax($this->cumulative);
		$content .= $this->showChart('chart1', $chart, $this->cumulative, $max);
		return $content;
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
	 * @param string $keyKey
	 * @param string $timeKey
	 * @param string $amountKey
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
		return $chart;
	}

	function getChartCumulative(array $chart) {
		foreach ($chart as &$sub) {
			$sum = 0;
			foreach ($sub as &$val) {
				$sum += $val[1];
				$val[1] = $sum;
			}
		}
		return $chart;
	}

	function getChartMax(array $chart) {
		$max = 0;
		foreach ($chart as $series) {
			foreach ($series as $pair) {
				$max = max($max, $pair[1]);
			}
		}
		return $max;
	}

	function showChart($divID, array $charts, array $cumulative, $max) {
		Index::getInstance()->addJQuery();
		Index::getInstance()->footer['flot'] = '
		<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
    	<script language="javascript" type="text/javascript" src="flot/jquery.flot.js"></script>
    	<script language="javascript" type="text/javascript" src="flot/jquery.flot.time.js"></script>';

		$content = '<div id="'.$divID.'" style="width: 950px; height:600px; border: solid 1px silver;"></div>';

		foreach ($charts as $key => &$rows) {
			$array = $rows ? array_values($rows) : array();
			$rows = 'var d'.$key.' = {
				label: "'.$key.'",
				data: '.json_encode($array).',
				bars: {
					show: true,
					barWidth: 24*60*60*1000*25,
					align: "center"
				}
			};';
		}

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
		$max *= 2;

		Index::getInstance()->footer[$divID] = '
    	<script type="text/javascript">
$(function () {
	'.implode("\n", $charts).'
	'.implode("\n", $cumulative).'
    $.plot($("#'.$divID.'"), [
    	d'.implode(", d", array_keys($charts)).',
    	c'.implode(", c", array_keys($charts)).'
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
