<?php

class OptimizeDB extends AppControllerBE {

	function render() {
		$tables = $this->db->getTables();
		$content = getDebug($tables);
		return $content;
	}

	function sidebar() {
		return $this->getActionButton('Optimize DB', 'optimize');
	}

	function optimizeAction() {
		$tables = $this->db->getTables();
		foreach ($tables as $name) {
			$query = "OPTIMIZE TABLE `".$name."`";
			echo $query;
			$this->db->perform($query);
			echo "\n";
		}
	}

}
