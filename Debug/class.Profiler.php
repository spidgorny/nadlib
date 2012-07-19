<?php

class Profiler {
	var $startTime;

	function Profiler($startTime = NULL) {
		$this->startTime = $startTime ? $startTime : $this->getMilliTime();
	}

	function getMilliTime() {
		list($usec, $sec) = explode(" ", microtime());
		$time = (float)$usec + (float)$sec;
		return $time;
	}

	function elapsed() {
		$endTime = $this->getMilliTime();
		$out = $endTime-$this->startTime;
		return number_format($out, 5, '.', '');
	}

	function Done() {
		$out = $this->elapsed();
		print("Done in $out seconds.");
	}

}
