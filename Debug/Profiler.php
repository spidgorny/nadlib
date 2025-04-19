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
 * @phpstan-consistent-constructor
 */
class Profiler
{

	public $startTime;

	public $endTime;

	public function __construct($startTime = null)
	{
		$this->startTime = $startTime ?: microtime(true);
	}

	public function restart(): void
	{
		$this->startTime = microtime(true);
		$this->endTime = null;
	}

	public function stop(): void
	{
		$this->endTime = microtime(true);
	}

	/**
     * Stops the timer so the elapsed value is preserved.
     */
    public function elapsed(): string
	{
		if (!$this->endTime) {
			$this->stop();
		}

		$out = $this->endTime - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	/**
     * Returns elapsed time without stopping the timer. Can be checked in a loop.
     */
    public function elapsedCont(): string
	{
		$out = microtime(true) - $this->startTime;
		return number_format($out, 5, '.', '');
	}

	/**
     * Restarts the timer, useful for something similar to setTimeout()
     */
    public function elapsedNext(): string
	{
		$since = $this->elapsed();
		$this->restart();
		return $since;
	}

	public function Done($isReturn = false): ?string
	{
		$out = number_format($this->elapsed(), 3);
		$content = sprintf('Done in %s seconds.', $out) . BR;
		if ($isReturn) {
			return $content;
		} else {
			print($content);
		}

        return null;
	}

	public function startTimer($method): void
	{
		TaylorProfiler::start($method);
	}

	public function stopTimer($method): void
	{
		TaylorProfiler::stop($method);
	}

	public function __toString(): string
	{
		return $this->elapsed() . '';
	}

	public static function sinceStart(): string
	{
		$p = new static(ifsetor($_SERVER['REQUEST_TIME_FLOAT'], $_SERVER['REQUEST_TIME']));
		return $p->elapsed();
	}

}
