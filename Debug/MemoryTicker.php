<?php

class MemoryTicker extends Ticker
{

	public $isFirstTick = true;

	public function tick(): void
	{
		if ($this->isFirstTick) {
			$this->isFirstTick = false;
			llog($_SERVER['REQUEST_URI']);
		}

		$mem = (float)TaylorProfiler::getMemUsage();
		if ($mem - $this->prevMemory > 0.1) {
			llog('Memory: ' . $mem);
			ob_start();
			debug_print_backtrace();
			$bt = ob_get_clean();
			llog($bt);
			$this->prevMemory = $mem;
		}

		if ($mem > 0.7) {
			llog($mem);
			echo '<pre>', PHP_EOL;
			debug_pre_print_backtrace();
			die;
		}
	}

}
