<?php

/**
 * Class AlterTable - it's similar to the AlterDB,
 * but AlterDB is using raw SQL dumps (parsed by TYPO3 lib) to detect changes.
 * This class stores the structure in JSON files and then reads them back.
 * This gives a more reliable comparison.
 */
class AlterTable extends AlterIndex {

	function renderTableStruct(array $struct, array $local) {
		$class = get_class($this->db);
		if ($class == 'dbLayerPDO') {
			$class = $this->db->getScheme();
			if ($class == 'sqlite') $class = 'dbLayerSQLite';
		}
		$func = 'renderTableStruct'.$class;
		$content[] = '<h5>'.$func.'</h5>';
		$content[] = call_user_func(array($this, $func), $struct, $local);
		return $content;
	}

	function renderTableStructMySQL(array $struct, array $local) {
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4>Table: '.$table.'</h4>';
			//$content .= '<pre>'.json_encode($desc['columns'], JSON_PRETTY_PRINT).'</pre>';

			$indexCompare = array();
			foreach ($desc['columns'] as $i => $index) {
				if (isset($local[$table])) {
					$localIndex = $local[$table]['columns'][$i];
					if ($localIndex) {
						unset($index['Cardinality'], $localIndex['Cardinality']);
						if (!$this->sameType($index, $localIndex)) {
							//$content .= getDebug($index, $localIndex);
							$line = array(
									'same'          => 'diff',
									'###TR_MORE###' => 'style="background: pink"',
								) + $index;
							$line['Type'] .= ' (local: ' . $localIndex['Type'] . ')';
							$indexCompare[] = $line;
							$indexCompare[] = array(
								'Field' => new HTMLTag('td', array(
									'colspan' => 10,
									'class'   => 'sql',
								), $this->getAlterQuery($table, $localIndex['Field'], $index)
								),
							);
						} else {
							//$content .= 'Same index: '.$index['Key_name'].' '.$localIndex['Key_name'].'<br />';
							$index['same'] = 'same';
							$index['###TR_MORE###'] = 'style="background: yellow"';
							$indexCompare[] = $index;
						}
					} else {
						$indexCompare[] = 							$line = array(
							'same'          => 'new',
							'###TR_MORE###' => 'style="background: red"',
						) +	$index;
						$indexCompare[] = array(
							'Field' => new HTMLTag('td', array(
								'colspan' => 10,
								'class'   => 'sql',
							), $this->getAddQuery($table, $index)
							),
						);
					}
				} else {
					$indexCompare[] = array(
						'Field' => new HTMLTag('td', array(
							'colspan' => 10,
							'class' => 'sql',
						), $this->getCreateQuery($table, $index)
						),
					);
					break;
				}
			}
			$s = new slTable($indexCompare, 'class="table" width="100%"', array (
				'same' =>				array (
					'name' => 'same',
				),
				'Field' =>				array (
					'name' => 'Field',
				),
				'Type' =>				array (
					'name' => 'Type',
				),
				'Collation' =>			array (
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

	function getAlterQuery($table, $oldName, array $index) {
		$query = 'ALTER TABLE '.$table.' MODIFY COLUMN '.$oldName.' '.$index['Field'].
		' '.$index['Type'].
		' '.(($index['Null'] == 'NO') ? 'NOT NULL' : 'NULL').
		' '.($index['Collation'] ? 'COLLATE '.$index['Collation'] : '').
		' '.($index['Default'] ? "DEFAULT '".$index['Default']."'" : '').
		' '.($index['Comment'] ? "COMMENT '".$index['Comment']."'" : '').
		' '.$index['Extra'];
		$link = $this->a($this->makeURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'sql' => $query,
		)), $query);
		return $link;
	}

	function getAddQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index['Field'].
		' '.$index['Type'].
		' '.(($index['Null'] == 'NO') ? 'NOT NULL' : 'NULL').
		' '.($index['Collation'] ? 'COLLATE '.$index['Collation'] : '').
		' '.($index['Default'] ? "DEFAULT '".$index['Default']."'" : '').
		' '.($index['Comment'] ? "COMMENT '".$index['Comment']."'" : '').
		' '.$index['Extra'];
		$link = $this->a($this->makeURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'sql' => $query,
		)), $query);
		return $link;
	}

	function getCreateQuery($table, array $index) {
		return 'CREATE TABLE '.$table.' (id int auto_increment);';
	}

	function sameType($index1, $index2) {
		$int = array('int(11)', 'INTEGER', 'integer', 'tinyint(1)', 'int');
		$text = array('text', 'varchar(255)', 'tinytext');
		$time = array('numeric', 'timestamp');
		$real = array('real', 'double', 'float');
		$t1 = $index1['Type'];
		$t2 = $index2['Type'];
		if ($t1 == $t2) {
			return true;
		} elseif (in_array($t1, $int) && in_array($t2, $int)) {
			return true;
		} elseif (in_array($t1, $text) && in_array($t2, $text)) {
			return true;
		} elseif (in_array($t1, $time) && in_array($t2, $time)) {
			return true;
		} elseif (in_array($t1, $real) && in_array($t2, $real)) {
			return true;
		} else {
			return false;
		}
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

	function renderTableStructdbLayerSQLite(array $struct, array $local) {
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4>Table: '.$table.'</h4>';

			$indexCompare = array();
			foreach ($desc['columns'] as $i => $index) {
				$localIndex = $local[$table]['columns'][$i];

				//unset($index['num'], $localIndex['num']);
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

			$s = new slTable($indexCompare, 'class="table nospacing"');
			$content .= $s;
		}
		return $content;
	}

	function runSQLAction() {
		$sql = $this->request->getTrim('sql');
		if (contains($sql, 'DROP') || contains($sql, 'TRUNCATE') || contains($sql, 'DELETE')) {
			throw new AccessDeniedException('Destructing query detected');
		} else {
			$this->db->perform($sql);
			$this->request->redirect(get_class().'?file='.$this->request->getTrimRequired('file'));
		}
	}

}
