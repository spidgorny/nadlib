<?php

class AlterTable extends AlterIndex {

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
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4>Table: '.$table.'</h4>';

			$indexCompare = array();
			foreach ($desc['columns'] as $i => $index) {
				$localIndex = $local[$table]['columns'][$i];
				unset($index['Cardinality'], $localIndex['Cardinality']);
				if ($index != $localIndex) {
					//$content .= getDebug($index, $localIndex);
					if (is_array($index)) {
						$indexCompare[] = array('same' => 'sql file',
							'###TR_MORE###' => 'style="background: pink"',
							) + $index;
					}
					if (is_array($localIndex)) {
						$indexCompare[] = array('same' => 'database',
							'###TR_MORE###' => 'style="background: pink"',
						) + $localIndex;
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
					//$content .= 'Same index: '.$index['Key_name'].' '.$localIndex['Key_name'].'<br />';
					$index['same'] = 'same';
					$index['###TR_MORE###'] = 'style="background: yellow"';
					$indexCompare[] = $index;
				}
			}
			$s = new slTable($indexCompare, 'class="table"', array (
				'same' =>				array (
					'name' => 'same',
				),
				'Field' =>				array (
					'name' => 'Field',
				),
				'Type' =>				array (
					'name' => 'Type',
				),
				'Collation' =>				array (
					'name' => 'Collation',
				),
				'Null' =>				array (
					'name' => 'Null',
				),
				'Key' =>				array (
					'name' => 'Key',
				),
				'Default' =>				array (
					'name' => 'Default',
				),
				'Extra' =>				array (
					'name' => 'Extra',
				),
/*				'Privileges' =>				array (
					'name' => 'Privileges',
				),
*/				'Comment' =>				array (
					'name' => 'Comment',
				),
			));
			$content .= $s;
		}
		return $content;
	}

}
