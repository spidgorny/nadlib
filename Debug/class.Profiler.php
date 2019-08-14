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
class Profiler
{

	var $startTime;

	var $endTime;

	function __construct($startTime = NULL)
	{
		$this->startTime = $startTime ? $startTime : microtime(true);
	}

	function stop()
	{
		$this->endTime = microtime(true);
	}

	function elapsed()
	{
		if (!$this->endTime) {
			$this->stop();
		}
		$out = $this->endTime - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	function elapsedCont()
	{
		$out = microtime(true) - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	function Done($isReturn = FALSE)
	{
		$out = number_format($this->elapsed(), 3);
		$content = "Done in $out seconds.";
		if ($isReturn) {
			return $content;
		} else {
			print($content);
		}
	}

	function startTimer($method)
	{
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->startTimer($method);
	}

	function stopTimer($method)
	{
		if (isset($GLOBALS['prof'])) $GLOBALS['prof']->stopTimer($method);
	}

	function __toString()
	{
		return $this->elapsed();
	}

}
