<?php

class YearOverview extends Controller {

	var $year;

	var $days = [];

	var $beginning;

	var $dowName = [
		1 => 'Mon',
		'Tue',
		'Wed',
		'Thu',
		'Fri',
		'Sat',
		'Sun',
	];

	var $maxIntensity = '#1e6823';

	function __construct($year = NULL)
	{
		$this->year = $year ?: date('Y');
		$this->beginning = mktime(0, 0, 0, 1, 1, $this->year);
		$leap = date('L');
		$date = $this->beginning;
		for ($i = 1; $i <= 365 + $leap; $i++) {
			$iso = date('Y-m-d', $date);
			$this->days[$iso] = 0;
			//$this->days[$iso] = rand(0, 100); // test
			$date = strtotime('+1 day', $date);
		}
	}

	function addActivity($day, $count) {
		$day = strtotime($day);
		$day = date('Y-m-d', $day);
		$this->days[$day] += $count;
	}

	/**
	 * The values in $this->days are supposed to be in [0, 100] range
	 */
	function normalize() {
		$max = max($this->days);
		if ($max) {
			foreach ($this->days as &$count) {
				$count = $count * 100 / $max;
			}
		}
	}

	function render() {
		// diff between Monday and first of Jan
		$diff = date('N', $this->beginning) - 1;
		$beginning = strtotime('-'.$diff.' days', $this->beginning);
		$content[] = '<table class="YearOverview">';
		$content[] = '<tr>
			<th></th>
			<th colspan="5">Jan</th>
			<th colspan="4">Feb</th>
			<th colspan="5">Mar</th>
			<th colspan="4">Apr</th>
			<th colspan="5">May</th>
			<th colspan="4">Jun</th>
			<th colspan="5">Jul</th>
			<th colspan="5">Aug</th>
			<th colspan="4">Sep</th>
			<th colspan="5">Oct</th>
			<th colspan="4">Nov</th>
			<th colspan="5">Dec</th>
		</tr>';
		for ($dow = 1; $dow <= 7; $dow++) {
			$date = strtotime('+'.($dow-1).' days', $beginning);	// first week Jan
			$content[] = '<tr>';
			$content[] = '<td>'.$this->dowName[$dow].'</td>';
			for ($w = 1; $w <= 53; $w++) {
				$iso = date('Y-m-d', $date);
				if (str_startsWith($iso, $this->year)) {
					if (isset($this->days[$iso])) {
						$intensity = $this->days[$iso];
						$color = new Color($this->maxIntensity);
						$sColor = $color->alter_color(0, $intensity - 100, 100-$intensity);
						$style = 'background-color: ' . $sColor;
					} else {
						$style = '';
					}
					$inTD = date('d', $date) == 1 ? '01' : '';
					$inTD = date('d', $date) == 31 ? '31' : $inTD;
					$inTD = $dow == 1 ? date('d', $date) : $inTD;
				} else {
					$style = '';
					$inTD = '';
				}
				$content[] = '<td style="'.$style.'">'.$inTD.'</td>';
				$date = strtotime('+7 days', $date);
			}
			$content[] = '</tr>';
		}
		$content[] = '</table>';
		return $content;
	}

}
