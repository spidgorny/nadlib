<?php

class MiniProfiler
{
	protected $timer = array();
	protected $lastName = array();

	function startTimer($name)
	{
		$this->timer[$name]['start'] = microtime(true);
		//$this->lastName[] = $name;
	}

	function stopTimer($name)
	{
		$this->timer[$name]['duration'] += microtime(true) - $this->timer[$name]['start'];
		$this->timer[$name]['times']++;
		$this->timer[$name]['start'] = microtime(true);
		//$this->lastName[] = $name;
	}

	function printTimers()
	{
		$table = array();
		foreach ($this->timer as $name => $t) {
			$table[] = array(
				'name' => $name,
				'duration' => number_format($t['duration'], 3, '.', ''),
				'times' => $t['times'],
			);
		}
		//$ac = array_column($table, 'duration');
		$ac = ArrayPlus::create($table)->column('duration')->getData();
		array_multisort($ac, SORT_DESC, $table);
		return new slTable($table);
	}

}
