<?php

class AlterTable extends AlterIndex {

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
							'Field' => new HTMLTag('td', array(
									'colspan' => 10,
								), $this->getAlterQuery($table, $index)
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

	function getAlterQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index['Field'].
		' '.$index['Type'].
		' '.(($index['Null'] == 'NO') ? 'NOT NULL' : 'NULL').
		' '.($index['Collation'] ? 'COLLATE '.$index['Collation'] : '').
		' '.($index['Default'] ? "DEFAULT '".$index['Default']."'" : '').
		' '.($index['Comment'] ? "COMMENT '".$index['Comment']."'" : '').
		' '.$index['Extra'];
		return $query;
	}

}
