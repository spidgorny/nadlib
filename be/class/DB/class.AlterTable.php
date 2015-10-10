<?php

/**
 * Class AlterTable - it's similar to the AlterDB,
 * but AlterDB is using raw SQL dumps (parsed by TYPO3 lib) to detect changes.
 * This class stores the structure in JSON files and then reads them back.
 * This gives a more reliable comparison.
 */
class AlterTable extends AlterIndex {

	var $different = 0;
	var $same = 0;
	var $missing = 0;

	function sidebar() {
		$content = array();
		$content[] = $this->showDBInfo();
		$content[] = $this->listFiles();
		return $content;
	}

	function renderTableStruct(array $struct, array $local) {
		$class = get_class($this->db);
		if ($class == 'dbLayerPDO') {
			$class = $this->db->getScheme();
			if ($class == 'sqlite') {
				$class = 'dbLayerSQLite';
			}
		}
		$func = 'renderTableStruct'.$class;
		$content[] = '<h5>'.$func.'</h5>';
		$content[] = call_user_func(array($this, $func), $struct, $local);
		return $content;
	}

	function renderTableStructMySQL(array $struct, array $local) {
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-'.$table.'">Table: '.$table.'</h4>';
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
						), $this->getCreateQuery($table, $desc['columns'])
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
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

	function getAddQuery($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$index['Field'].
		' '.$index['Type'].$this->getFieldParams($index);
		$link = $this->a($this->makeURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

	function getFieldParams(array $index) {
		$default = $index['Default']
			? (in_array($index['Default'], $this->db->getReserved())
				? $index['Default']
				: $this->db->quoteSQL($index['Default']))
			: '';
		return ' '.trim(
			(($index['Null'] == 'NO') ? 'NOT NULL' : 'NULL').
		' '.($index['Collation'] ? 'COLLATE '.$index['Collation'] : '').
		' '.($index['Default'] ? "DEFAULT ".$default : '').		// must not be quoted for CURRENT_TIMESTAMP
		' '.($index['Comment'] ? "COMMENT '".$index['Comment']."'" : '').
		' '.(($index['Key'] == 'PRI') ? "PRIMARY KEY" : '').
		' '.$index['Extra']);
	}

	function getCreateQueryMySQL($table, array $columns) {
		$set = array();
		foreach ($columns as $col) {
			$set[] = $this->db->quoteKey($col['Field']).' '.$col['Type'].$this->getFieldParams($col);
		}
		//debug($col);
		return 'CREATE TABLE '.$table.' ('.implode(",\n", $set).');';
	}

	function getCreateQueryDBLayer($table, array $columns) {
		$set = array();
		foreach ($columns as $col) {
			$set[] = $col['name'].' '.$col['type'].' '.($col['notnull'] ? 'NOT NULL' : 'NULL');
		}
		//debug($col);
		return 'CREATE TABLE '.$table.' ('.implode(",\n", $set).');';
	}

	function sameType($index1, $index2) {
		$int = array('int(11)', 'INTEGER', 'integer', 'tinyint(1)', 'int');
		$text = array('text', 'varchar(255)', 'tinytext');
		$time = array('numeric', 'timestamp', 'datetime');
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
			$content .= '<h4 id="table-'.$table.'">Table: '.$table.'</h4>';

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
			' '.$index['Type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

	function getChangeQueryDBLayer($table, array $index) {
		$query = 'ALTER TABLE '.$table.' ALTER COLUMN '.$index['Field'].' '.$index['Field'].
			' '.$index['Type'].
			' '.(($index['len'] > 0) ? ' ('.$index['len'].')' : '').
			' '.($index['not null'] ? 'NOT NULL' : 'NULL');
		return $query;
	}

	function renderTableStructdbLayerSQLite(array $struct, array $local) {
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-'.$table.'">Table: '.$table.'</h4>';

			$indexCompare = array();
			foreach ($desc['columns'] as $i => $index) {
				$index = $this->convertFromOtherDB($index);
				$localIndex = $local[$table]['columns'][$i];
				if ($localIndex) {
					$localIndex = $this->convertFromOtherDB($localIndex);

					$index['Field'] = $i;
					$localIndex['Field'] = $i;

					if ($this->sameType($index, $localIndex)) {
						$indexCompare[] = array('same' => 'sql file',
								'###TR_MORE###' => 'style="background: lightgreen"',
								'Field' => $i,
							) + $index;
						$this->same++;
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
							'Type' => new HTMLTag('td', array(
								'colspan' => 5,
							), $localIndex['Type']
								? $this->getChangeQueryDBLayer($table, $index)
								: $this->getAlterQueryDBLayer($table, $index)
							)
						);
						$this->different++;
					}
				} else {
					$indexCompare[] = array('same' => 'json file',
							'###TR_MORE###' => 'style="background: yellow"',
						) + $index;
					$indexCompare[] = array(
						'same' => 'missing',
						'Type' => new HTMLTag('td', array(
							'colspan' => 5,
						), $this->getAddQuery($table, $index)
						),
					);
					$this->missing++;
				}

				//debug($index, $localIndex); exit();
			}

			$s = new slTable($indexCompare, 'class="table nospacing"');
			$content .= $s;
		}
		return $content;
	}

	function convertFromOtherDB(array $desc) {
		if (isset($desc['cid']) || isset($desc['pk'])) {    // MySQL
			$original = $desc;
			unset($desc['cid']);
			$desc['Field'] = $desc['name'];
			unset($desc['name']);
			$desc['Type'] = $desc['type'];
			unset($desc['type']);
			$desc['Null'] = $desc['notnull'] ? 'NO' : 'YES';
			unset($desc['notnull']);
			$desc['Default'] = $desc['dflt_value'];
			$desc['Default'] = $this->unQuote($desc['Default']);
			unset($desc['dflt_value']);
			$desc['Extra'] = $desc['pk'] ? 'PRIMARY_KEY' : '';
			unset($desc['pk']);
			//debug($original, $desc); exit();
		}
		return $desc;
	}

	function unQuote($string) {
		$first = $string[0];
		if ($first == '"' || $first == "'") {
			$string = str_replace($first, '', $string);
		}
		return $string;
	}

	function runSQLAction() {
		$table = $this->request->getTrimRequired('table');
		$sql = $this->request->getTrim('sql');
		if (   contains($sql, 'DROP')
			|| contains($sql, 'TRUNCATE')
			|| contains($sql, 'DELETE')) {
			throw new AccessDeniedException('Destructing query detected');
		} else {
			$this->db->perform($sql);
			$this->request->redirect(get_class().
				'?file='.$this->request->getTrimRequired('file').'#table-'.$table);
		}
	}

}
