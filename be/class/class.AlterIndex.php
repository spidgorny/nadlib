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
		if (phpversion() > '5.3') {
			$json = json_encode($struct, JSON_PRETTY_PRINT);
		} else {
			$json = json_encode($struct);
		}

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
		if ($this->jsonFile && is_readable($this->jsonFile)) {
			$struct = file_get_contents($this->jsonFile);
			$struct = json_decode($struct, true);

			$local = $this->getDBStruct();

			$content = $this->renderTableStruct($struct, $local);
		}
		return $content;
	}

	function renderTableStruct(array $struct, array $local) {
		foreach ($struct as $table => $desc) {
			$content .= '<h4>Table: '.$table.'</h4>';

			$indexCompare = array();
			foreach ($desc['indexes'] as $i => $index) {
				$localIndex = $local[$table]['indexes'][$i];
				//unset($index['Cardinality'], $localIndex['Cardinality']);
				if ($index != $localIndex) {
					//$content .= getDebug($index, $localIndex);
					if (is_array($index)) {
						$indexCompare[] = $index;
					}
					if (is_array($localIndex)) {
						$indexCompare[] = $localIndex;
					} else {
						$indexCompare[] = array(
							'Table' => new HTMLTag('td', array(
									'colspan' => 10,
								), 'CREATE '.($index['Non_unique'] ? '' : 'UNIQUE' ).
								' INDEX '.$index['Key_name'].
								' ON '.$index['Table'].' ('.$index['Key_name'].')'
							),
						);
					}
				} else {
					$content .= 'Same index: '.$index['Key_name'].' '.$localIndex['Key_name'].'<br />';
				}
			}
			$content .= new slTable($indexCompare, 'class="table"', array(
				'Table' => 'Table',
				'Non_unique' => 'Non_unique',
				'Key_name' => 'Key_name',
				'Seq_in_index' => 'Seq_in_index',
				'Column_name' => 'Column_name',
				'Collation' => 'Collation',
				'Cardinality' => 'Cardinality',
				//'Sub_part' => 'Sub_part',
				//'Packed' => 'Packed',
				'Null' => 'Null',
				'Index_type' => 'Index_type',
				//'Comment' => 'Comment',
				//'Index_comment' => 'Index_comment',
			));
		}
		return $content;
	}

}
