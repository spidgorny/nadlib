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

	function __construct($startTime = null)
	{
		$this->startTime = $startTime ? $startTime : microtime(true);
	}

	function restart()
	{
		$this->startTime = microtime(true);
		$this->endTime = null;
	}

	function stop()
	{
		$this->endTime = microtime(true);
	}

	/**
	 * Stops the timer so the elapsed value is preserved.
	 * @return float
	 */
	function elapsed()
	{
		if (!$this->endTime) {
			$this->stop();
		}
		$out = $this->endTime - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	/**
	 * Returns elapsed time without stopping the timer. Can be checked in a loop.
	 * @return string
	 */
	function elapsedCont()
	{
		$out = microtime(true) - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	/**
	 * Restarts the timer, useful for something similar to setTimeout()
	 * @return float
	 */
	function elapsedNext()
	{
		$since = $this->elapsed();
		$this->restart();
		return $since;
	}

	function Done($isReturn = FALSE)
	{
		$out = number_format($this->elapsed(), 3);
		$content = "Done in $out seconds." . BR;
		if ($isReturn) {
			return $content;
		} else {
			print($content);
		}
	}

	function startTimer($method)
	{
		TaylorProfiler::start($method);
	}

	function stopTimer($method)
	{
		TaylorProfiler::stop($method);
	}

	function __toString()
	{
		return $this->elapsed() . '';
	}

}
