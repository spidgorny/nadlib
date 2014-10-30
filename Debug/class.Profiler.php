<?php

class Profiler {
	var $startTime;

	function Profiler($startTime = NULL) {
		$this->startTime = $startTime ? $startTime : $this->getMilliTime();
	}

	static function getMilliTime() {
		list($usec, $sec) = explode(" ", microtime());
		$time = (float)$usec + (float)$sec;
		return $time;
	}

	/**
	 * @return float
	 */
	function elapsed() {
		$endTime = $this->getMilliTime();
		$out = $endTime-$this->startTime;
		return number_format($out, 5, '.', '');
	}

	function Done($isReturn = FALSE) {
		$out = number_format($this->elapsed(), 3);
		$content = "Done in $out seconds.";
		if ($isReturn) {
			return $content;
		} else {
			print($content);
		}
	}

	function startTimer($method) {
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->startTimer($method);
	}

	function stopTimer($method) {
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->stopTimer($method);
	}

}
