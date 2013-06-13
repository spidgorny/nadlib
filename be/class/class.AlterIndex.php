<?php

class AlterIndex extends AppControllerBE {

	/**
	 * @var string
	 */
	var $jsonFile;

	function __construct() {
		parent::__construct();
		$c = Config::getInstance();
		$this->jsonFile = $c->appRoot.'/sql/'.$this->db->db.'.json';
	}

	function sidebar() {
		return $this->getActionButton('Save DB Struct', 'saveStruct');
	}

	function saveStructAction() {
		$struct = $this->getDBStruct();
		$json = json_encode($struct, JSON_PRETTY_PRINT);

		file_put_contents($this->jsonFile, $json);
		return 'Saved: '.strlen($json).'<br />';
	}

	function getDBStruct() {
		$result = array();
		$tables = $this->db->getTables();
		foreach ($tables as $t) {
			$struct = $this->db->getTableColumns($t);
			$indexes = $this->db->getIndexesFrom($t);
			$result[$t] = array(
				'columns' => $struct,
				'indexes' => $indexes,
			);
		}
		return $result;
	}

	function render() {
		$content = $this->performAction();
		$struct = file_get_contents($this->jsonFile);
		$struct = json_decode($struct, true);

		$local = $this->getDBStruct();

		foreach ($struct as $table => $desc) {
			$content .= '<h2>Table: '.$table.'</h2>';

			foreach ($desc['indexes'] as $i => $index) {
				$localIndex = $local[$table]['indexes'][$i];
				//unset($index['Cardinality'], $localIndex['Cardinality']);
				if ($index != $localIndex) {
					//$content .= getDebug($index, $localIndex);
					$content .= new slTable(array($index, $localIndex), 'class="table"');
				} else {
					$content .= 'Same index: '.$index['Key_name'].' '.$localIndex['Key_name'].'<br />';
				}
			}
		}
		return $content;
	}

}
