<?php

/**
 * Class Flot - is drawing a flot chart.
 */
class Flot extends AppController {

	protected $colors = array(
		'#edc240',
		'#afd8f8',
		'#cb4b4b',
		//'#edc240',
		//'#afd8f8',
		//'#cb4b4b',
		"#9440ed",
		"#40ed94",
		'#4da74d',
	);

	/**
	 * Raw data single table
	 * @var array
	 */
	public $data;

	protected $keyKey, $timeKey, $amountKey;

	/**
	 * A source table pivoted (grouped by) $keyKey
	 * @var array
	 */
	public $chart;

	/**
	 * @var array - these are line charts, multiple series as well
	 */
	public $cumulative = array();

	public $min = 0;

	/**
	 * @var int - max value for cumulative (max of max possible)
	 */
	public $max = 1;

	public $cMin = 0;

	public $cMax = 1;

	public $width = '950px';

	public $height = '600px';

	public $barWidth = '24*60*60*1000*25';

	/**
	 * @var string
	 */
	var $flotPath = 'components/flot/flot/';

	/**
	 * @param array $data	- source data
	 * @param $keyKey		- group by field (distinct charts, lines)
	 * @param $timeKey		- time field
	 * @param $amountKey	- value (numeric) field
	 */
	function __construct(array $data, $keyKey, $timeKey, $amountKey) {
		parent::__construct();
		$this->data = $data;
		$this->keyKey = $keyKey;
		$this->timeKey = $timeKey;
		$this->amountKey = $amountKey;

		$this->chart = $this->getChartTable($this->data);
		$this->min = $this->getChartMax($this->chart, 'min');
		$this->max = $this->getChartMax($this->chart);

		$this->cumulative = $this->getChartCumulative($this->chart);
		$this->cMin = $this->getChartMax($this->cumulative, 'min');
		$this->cMax = $this->getChartMax($this->cumulative);

		// add this manually before rendering if needed
		//$this->cumulative = $this->getChartCumulative($this->chart);
		//$this->max = $this->getChartMax($this->cumulative);
	}

	function setFlot($path) {
		$this->flotPath = $path;
	}

	/**
	 * Fixed for Posa Cards
	 *
	 * @internal param array $data
	 * @param string $divID
	 * @throws Exception
	 * @return array
	 * array[19]
	 * 1309471200    array[2]
	 * 0    integer    1309471200000
	 * 1    integer    0
	 * 1314828000    array[2]
	 * 0    integer    1314828000000
	 * 1    integer    39
	 */
	function render($divID = 'chart1') {
		$content = '';
		if (!is_dir($this->flotPath)) {
			throw new Exception($this->flotPath.' is not correct');
		}
		$content .= $this->showChart($divID, $this->chart, $this->cumulative, $this->max);
		return $content;
	}

	function renderCumulative($divID = 'chart1') {
		$content = '';
		$content .= $this->showChart($divID, $this->chart, $this->cumulative, $this->max);
		return $content;
	}

	function appendCumulative(array $data) {
		//debug($this->cumulative, $data);
		$cumulative2 = array();
		foreach ($this->cumulative as $series) {
			$cumulative2 = array_merge($cumulative2, $series);
		}
		$cumulative = array_values($cumulative2);
		$dataClass = array();
		foreach ($data as $i => &$row) {
			$color = $this->colors[$row[$this->keyKey]-1];
			$dataClass[$i] = '" style="background: white; color: '.$color;
			//$row['###TD_CLASS###'] = '" style="background: white; color: '.$color;

			//$row['cumulative'] = $cumulative[$i][1];
			$jsTime = strtotime($i)*1000;
			$row['cumulative'] = $this->cumulative['Total'][$jsTime][1];
		}
		return $data;
	}

	/**
	 * Return a multitude of rows which are extracted by the $keyKey.
	 * Each row is an assoc array with $timeKey keys and $amountKey values.
	 * Uses strtotime() so the $timeKey values should be PHP parsable
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
			$key = $this->keyKey ? $row[$this->keyKey] : 'one';
			$time = $row[$this->timeKey];
			if ($time) {
				$time = is_string($time) ? strtotime($time) : $time;
				$chart[$key][$time] = array($time*1000, $row[$this->amountKey]);
			} else {
				unset($rows[$i]);
			}
		}
		return $chart;
	}

	function getChartCumulative(array $chart) {
		foreach ($chart as &$sub) {
			ksort($sub);
			$sum = 0;
			foreach ($sub as &$val) {
				$sum += $val[1];
				$val[1] = $sum;
			}
			unset($val);
		}
		return $chart;
	}

	static function getChartMax(array $chart, $min = false) {
		$max = 0;
		foreach ($chart as $series) {
			foreach ($series as $pair) {
				$max = $min ? min($max, $pair[1]) : max($max, $pair[1]);
			}
		}
		return $max;
	}

	function showChart($divID, array $charts, array $cumulative) {
		$this->index->addJQuery();
		$this->index->footer['flot'] = '
		<!--[if lte IE 8]><script language="javascript" type="text/javascript"
			src="'.$this->flotPath.'excanvas.min.js"></script><![endif]-->
    	<script language="javascript" type="text/javascript"
    	    src="'.$this->flotPath.'jquery.flot.js"></script>
    	<script language="javascript" type="text/javascript"
    	    src="'.$this->flotPath.'jquery.flot.stack.js"></script>
    	<script language="javascript" type="text/javascript"
    	    src="'.$this->flotPath.'jquery.flot.time.js"></script>';

		$content = '<div id="'.$divID.'" style="
			width: '.$this->width.';
			height: '.$this->height.';
			border: none 0px silver;"></div>';

		$dKeys = array();
		foreach ($charts as $key => &$rows) {
			$jsKey = 'd_'.URL::friendlyURL($key);
			$jsKey = str_replace('-', '_', $jsKey);
			$dKeys[] = $jsKey;
			$array = $rows ? array_values($rows) : array();
			$rows = 'var '.$jsKey.' = {
				label: "'.$key.'",
				data: '.json_encode($array).',
				stack: true,
				bars: {
					show: true,
					barWidth: '.$this->barWidth.',
					align: "center"
				}
			};';
		}

		$cKeys = array();
		foreach ($cumulative as $key => &$rows) {
			$jsKey = 'c_'.URL::friendlyURL($key);
			$jsKey = str_replace('-', '_', $jsKey);
			$cKeys[] = $jsKey;
			$array = $rows ? array_values($rows) : array();
			$rows = 'var '.$jsKey.' = {
				data: '.json_encode($array).',
				lines: {
					show: true,
					fill: false
				},
				yaxis: 2
			};';
		}
		//$max *= 2;

		$this->index->footer[$divID] = '
    	<script type="text/javascript">
jQuery("document").ready(function ($) {
	'.implode("\n", $charts).'
	'.implode("\n", $cumulative).'
    $.plot($("#'.$divID.'"), [
    	'.implode(", ", $dKeys).',
    	'.implode(", ", $cKeys).'
    ], {
    	xaxis: {
    		mode: "time"
    	},
    	yaxes: [ {
    			min: '.$this->min.',
    			max: '.$this->max.'
    		}, {
    			min: '.$this->cMin.',
    		    max: '.$this->cMax.',
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
