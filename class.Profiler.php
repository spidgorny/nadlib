<?php

class Profiler {
	var $startTime;

	function Profiler() {
		$this->startTime = $this->getMilliTime();
	}

	function getMilliTime() {
		list($usec, $sec) = explode(" ", microtime());
		$time = (float)$usec + (float)$sec;
		return $time;
	}

	function elapsed() {
		$endTime = $this->getMilliTime();
		$out = $endTime-$this->startTime;
		return $out;
	}

	function Done() {
		$out = number_format($this->elapsed(), 3);
		print("Done in $out seconds.");
	}
};

?>