<?php

/**
 * Class AlterTable - it's similar to the AlterDB,
 * but AlterDB is using raw SQL dumps (parsed by TYPO3 lib) to detect changes.
 * This class stores the structure in JSON files and then reads them back.
 * This gives a more reliable comparison.
 */
class AlterTable extends AlterIndex {

	function renderTableStruct(array $struct, array $local) {
		$func = 'renderTableStruct'.get_class($this->db);
		return call_user_func(array($this, $func), $struct, $local);
	}

	function renderTableStructMySQL(array $struct, array $local) {
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

	function renderTableStructdbLayerBL(array $struct, array $local) {
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4>Table: '.$table.'</h4>';

			$indexCompare = array();
			foreach ($desc['columns'] as $i => $index) {
				$localIndex = $local[$table]['columns'][$i];

				unset($index['num'], $localIndex['num']);
				$index['Field'] = $i;
				$localIndex['Field'] = $i;

				if ($index == $localIndex) {
					$indexCompare[] = array('same' => 'sql file',
						'###TR_MORE###' => 'style="background: lightgreen"',
						'Field' => $i,
						) + $index;
				} else {
					$indexCompare[] = array('same' => 'json file',
						'###TR_MORE###' => 'style="background: yellow"',
						) + $index;
					$indexCompare[] = array('same' => 'database',
						'###TR_MORE###' => 'style="color: white; background: red"',
						'Field' => $i,
						) + $localIndex;
					$indexCompare[] = array('same' => 'ALTER',
						'###TR_MORE###' => 'style="color: white; background: green"',
						'Field' => $i,
						'type' => new HTMLTag('td', array(
							'colspan' => 5,
						), $localIndex['type']
							? $this->getChangeQueryDBLayer($table, $index)
							: $this->getAlterQueryDBLayer($table, $index)
						)
					);
				}
			}

			$s = new slTable($indexCompare, 'class="table"');
			$content .= $s;
		}
		return $content;
	}

	function getAlterQueryDBLayer($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index['Field'].
			' '.$index['type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

	function getChangeQueryDBLayer($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ALTER COLUMN '.$index['Field'].' '.$index['Field'].
			' '.$index['type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

}
