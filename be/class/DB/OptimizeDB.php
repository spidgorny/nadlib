<?php

/**
 * Class OptimizeDB
 * Calling OPTIMIZE TABLE X; for each table
 */
class OptimizeDB extends AppControllerBE
{

	public function render(): string
	{
		$tables = $this->db->getTables();
		return getDebug($tables);
	}

	public function sidebar()
	{
		return $this->getActionButton('Optimize DB', 'optimize');
	}

	public function optimizeAction(): void
	{
		$tables = $this->db->getTables();
		$pb = new ProgressBar();
		foreach ($tables as $i => $name) {
			$query = "OPTIMIZE TABLE `" . $name . "`";
			echo $query;
			$this->db->perform($query);
			echo "\n";
			$pb->setProgressBarProgress($i * 100 / count($tables));
		}
	}

}
