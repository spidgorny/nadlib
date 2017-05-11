<?php

/**
 * pdepend --summary-xml=pdepend-summary.xml class/Home/ && php metric.php
 * Class Metric
 */
class Metric
{

	protected $thresholds = [
		'cyclo-loc'    => [0.16, 0.20, 0.24],
		'loc-nom'      => [7, 10, 13],
		'nom-noc'      => [4, 7, 10],
		'noc-nop'      => [6, 17, 26],
		'calls-nom'    => [2.01, 2.62, 3.2],
		'fanout-calls' => [0.56, 0.62, 0.68],
		'andc'         => [0.25, 0.41, 0.57],
		'ahh'          => [0.09, 0.21, 0.32],
		'cyclo'		   => [null, null, 6],
	];

	public function __construct()
	{
	}

	public function render()
	{
		$save = true;

		$file = getcwd() . '/pdepend-summary.xml';
		$xml = simplexml_load_file($file);
		$attr = (array)($xml->attributes());
//$attr = (array)($xml->package[0]->class[0]->attributes());
		$attr = $attr['@attributes'];
		$attr['cyclo'] = $attr['cloc'];

		$props = $this->computeProportions($attr);

		$logLines = file(getcwd() . '/metric.log');
		$last = end($logLines);
		if ($last) {
			$last = json_decode($last, true);
		}
		if ($last == $attr + $props) {
			$last = $logLines[sizeof($logLines)-2];	// prev last
			$last = json_decode($last, true);
			$save = false;
		}

		foreach ($attr + $props as $name => $value) {
			$warning = null;
			$limits = ifsetor($this->thresholds[$name]);
			if ($limits) {
				if ($value > $limits[2]) {
					$warning = 'Too high (>' . $limits[2] . ')';
				} else {
					$percent = ($value - $limits[0]) * 100 / $limits[2];
					$warning = 'OK ('.round($percent).'%)';
				}
			}

			$lastTime = null;
			if ($last[$name] != $value) {
				$lastTime = 'was '.$last[$name];
			}
			echo tabify([$name, $value, $lastTime, $warning]), PHP_EOL;
		}

		$save = $save && ifsetor($last['generated']) != $attr['generated'];
		if ($save) {
			file_put_contents(getcwd() . '/metric.log',
				json_encode($attr + $props) . PHP_EOL,
				FILE_APPEND);
		}
	}

	/**
	 * Computes the proportions between the given metrics.
	 *
	 * @param  array $metrics The aggregated project metrics.
	 * @return array(string => float)
	 */
	protected function computeProportions(array $metrics)
	{
		$orders = [
			['cyclo', 'loc', 'nom', 'noc', 'nop'],
			['fanout', 'calls', 'nom'],
		];

		$proportions = [];
		foreach ($orders as $names) {
			for ($i = 1, $c = count($names); $i < $c; ++$i) {
				$value1 = $metrics[$names[$i]];
				$value2 = $metrics[$names[$i - 1]];

				$identifier = "{$names[$i - 1]}-{$names[$i]}";

				$proportions[$identifier] = 0;
				if ($value1 > 0) {
					$proportions[$identifier] = round($value2 / $value1, 3);
				}
			}
		}

		return $proportions;
	}

}
