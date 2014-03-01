<?php

/**
 * Class Profiler
 * Usage:
 * $p1 = new Profiler();
 * sleep(1);
 * echo $p1->elapsed();
 *
 * $p2 = new Profiler();
 * sleep(1);
 * $p2->stop();
 * sleep(1);
 * echo $p2->elapsed();
 */
class Profiler {

	var $startTime;

	var $endTime;

	function Profiler($startTime = NULL) {
		$this->startTime = $startTime ? $startTime : microtime(true);
	}

	function stop() {
		$this->endTime = microtime(true);
	}

	function elapsed() {
		if (!$this->endTime) {
			$this->stop();
		}
		$out = $this->endTime-$this->startTime;
		return number_format($out, 5, '.', '');
	}

	function Done() {
		$out = $this->elapsed();
		print("Done in $out seconds.");
	}

}
