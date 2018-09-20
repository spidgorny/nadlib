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

	/**
	 * @var AlterTableMySQL|AlterTablePostgres
	 */
	var $handler;

	function __construct()
	{
		parent::__construct();
		$this->setHandler();
	}

	function setHandler()
	{
		$class = $this->getDBclass();
		if ($class == 'mysql') {
			$this->handler = new AlterTableMySQL($this->db);
		} elseif ($class == 'DBLayer') {
			$this->handler = new AlterTablePostgres($this->db);
		} elseif ($class == 'DBLayerSQLite') {
			$this->handler = new AlterTableSQLite($this->db);
		} else {
			throw new Exception('Undefined AlterTable handler');
		}
	}

	function sidebar()
	{
		$content = array();
		$content[] = $this->showDBInfo();
		$content[] = $this->listFiles();
		return $content;
	}

	function renderTableStruct(array $struct, array $local)
	{
		$class = $this->getDBclass();
		$func = 'renderTableStruct';
		$func = 'compareStruct';
		$content[] = '<h5>' . $func . ' (' . $class . ')</h5>';
		$content[] = call_user_func(array($this, $func), $struct, $local);
		return $content;
	}

	function compareStruct(array $struct, array $local)
	{
		$content = '';
		//debug(array_keys($local));
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-' . $table . '">Table: ' . $table . '</h4>';
			//$content .= '<pre>'.json_encode($desc['columns'], JSON_PRETTY_PRINT).'</pre>';

			if (isset($local[$table])) {
				$indexCompare = $this->compareTables($table, $desc['columns'], $local[$table]['columns']);
			} else {
				$indexCompare = [array(
					'action' => new HTMLTag('td', array(
						'colspan' => 10,
						'class' => 'sql',
					), $this->click($table, $this->handler->getCreateQuery($table, $desc['columns']))
					),
				)];
			}
			$s = new slTable($indexCompare, 'class="table" width="100%"', array(
				'same' => array(
					'name' => 'same',
				),
				'fromFile' => array(
					'name' => 'From File',
				),
				'fromDB' => array(
					'name' => 'From DB',
				),
				'action' => 'Action',
			));
			$content .= $s;
		}
		return $content;
	}

	function compareTables($table, array $fromFile, array $fromDatabase)
	{
		$indexCompare = array();
		foreach ($fromFile as $i => $index) {
			$localIndex = ifsetor($fromDatabase[$i]);
			$fileField = TableField::init($index);
			if ($localIndex) {
				$localField = TableField::init($localIndex);
				if (!$this->handler->sameFieldType($fileField, $localField)) {
					$alterQuery = $this->handler->getAlterQuery($table, $localField->field, $fileField);
					$indexCompare[] = [
						'same' => 'diff',
						'###TR_MORE###' => 'style="background: pink"',
						'fromFile' => $fileField . '',
						'fromDB' => $localField . '',
						'action' => new HTMLTag('td', array(
							'colspan' => 10,
							'class' => 'sql',
						), $this->click($table, $alterQuery))
					];
				} else {
					$indexCompare[] = [
						'same' => 'same',
						'###TR_MORE###' => 'style="background: yellow"',
						'fromFile' => $fileField . '',
						'fromDB' => $localField . '',
					];
				}
			} else {
				$indexCompare[] = array(
					'same' => 'new',
					'###TR_MORE###' => 'style="background: red"',
					'fromFile' => $fileField . '',
					'fromDB' => '-',
					'action' => new HTMLTag('td', array(
						'colspan' => 10,
						'class' => 'sql',
					), $this->click($table, $this->handler->getAddQuery($table, $fileField)))
				);
			}
		}
		return $indexCompare;
	}

	function click($table, $query)
	{
		$link = $this->a($this->makeURL(array(
			'c' => get_class($this),
			'file' => basename($this->jsonFile),
			'action' => 'runSQL',
			'table' => $table,
			'sql' => $query,
		)), $query);
		return $link;
	}

	/**
	 *
	 * @param array $struct
	 * @param array $local
	 * @return string
	 */
	function renderTableStructdbLayerBL(array $struct, array $local)
	{
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-' . $table . '">Table: ' . $table . '</h4>';

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
							? $this->handler->getChangeQuery($table, $index)
							: $this->handler->getAlterQuery($table, $index)
						)
					);
				}
			}

			$s = new slTable($indexCompare, 'class="table"');
			$content .= $s;
		}
		return $content;
	}

	function renderTableStructdbLayerSQLite(array $struct, array $local)
	{
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-' . $table . '">Table: ' . $table . '</h4>';

			$indexCompare = array();
			foreach ($desc['columns'] as $i => $index) {
				$index = $this->convertFromOtherDB($index);    // TODO: make it TableField
				$localIndex = $local[$table]['columns'][$i];
				if ($localIndex) {
					$localIndex = $this->convertFromOtherDB($localIndex);    // TODO: make it TableField

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
								? $this->handler->getChangeQuery($table, $index)
								: $this->handler->getAlterQuery($table, $index)
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
						), $this->handler->getAddQuery($table, $index)
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

	function runSQLAction()
	{
		$table = $this->request->getTrimRequired('table');
		$sql = $this->request->getTrim('sql');
		if (contains($sql, 'DROP')
			|| contains($sql, 'TRUNCATE')
			|| contains($sql, 'DELETE')) {
			throw new AccessDeniedException('Destructing query detected');
		} else {
			$this->db->perform($sql);
			$this->request->redirect(get_class($this) .
				'?file=' . $this->request->getTrimRequired('file') . '#table-' . $table);
		}
	}

	/**
	 * @return string
	 */
	protected function getDBclass()
	{
		$class = get_class($this->db);
		if ($class == 'DBLayerPDO') {
			$class = $this->db->getScheme();
			if ($class == 'sqlite') {
				$class = 'DBLayerSQLite';
				return $class;
			}
			return $class;
		}
		return $class;
	}

}
