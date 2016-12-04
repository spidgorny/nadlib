<?php

class AlterIndex extends AppControllerBE {

	/**
	 * @var string
	 */
	var $jsonFile;

	/**
	 * @var dbLayerBase
	 */
	var $db;

	function __construct() {
		parent::__construct();
		$host = gethostname() ?: $_SERVER['SERVER_NAME'];
		$filename = $this->request->getFilename('file') ?: $host .'-'. $this->db->database.'.json';
		$this->jsonFile = $this->config->appRoot.'/sql/'.$filename;

		if (false) {
			require_once $this->config->appRoot.'/constants.php';
			$GLOBALS['dbLayer'] = new dbLayerBL('buglog', PG_DB_LOGIN, PG_DB_PASSW, PG_DB_HOSTN);
			$this->db = $GLOBALS['dbLayer'];
//			$this->config->db = $GLOBALS['dbLayer'];
//			$this->config->qb->db = $GLOBALS['dbLayer'];
			$this->jsonFile = $this->config->appRoot.'/sql/buglog_dev.json';
		}

	}

	function sidebar() {
		$content = array();
		if (class_exists('AdminPage')) {
			$ap = new AdminPage();
			$content[] = $ap->sidebar();
		}

		$content[] = $this->showDBInfo();
		$content[] = $this->listFiles();

		return $content;
	}

	function showDBInfo() {
		$content[] = 'Schema: '.$this->db->getScheme().BR;
		$content[] = 'Wrapper: '.get_class($this->db).BR;
		$content[] = 'DB: '.$this->db->database.BR;
		$content[] = 'File: '.basename($this->jsonFile).BR;
		if ($this->db->database) {
			$content[] = $this->getActionButton('Save DB Struct', 'saveStruct', NULL, array(), 'btn btn-info');
		}
		return $content;
	}

	function listFiles() {
		$li = array();
		$files = new ListFilesIn($this->config->appRoot.'/sql/');
		foreach ($files as $file) {
			/** @var $file SplFileInfo */
			if ($file->getExtension() == 'json') {
				$li[] = $this->a(new URL(NULL, array(
						'c' => get_class($this),
						'file' => basename($file),
					)), basename($file)) .
					'<div style="float: right;">[' . date('Y-m-d H:i', $file->getCTime()) . ']</div>';
			}
		}
		$ul = new UL($li);
		$content[] = $ul;
		return $content;
	}

	function saveStructAction() {
		$struct = $this->getDBStruct();
		if (phpversion() > '5.4') {
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
			$struct = $this->db->getTableColumnsEx($t);
			$indexes = $this->db->getIndexesFrom($t);
			$result[$t] = array(
				'columns' => $struct,
				'indexes' => $indexes,
			);
		}
		return $result;
	}

	function render() {
		$content[] = $this->performAction();
		if ($this->jsonFile && is_readable($this->jsonFile)) {
			$struct = file_get_contents($this->jsonFile);
			$struct = json_decode($struct, true);
			if (!$struct) {
				throw new Exception('JSON file reading error');
			}

			$local = $this->getDBStruct();

			$content[] = $this->renderTableStruct($struct, $local);
		} else {
			$content[] = '<div class="message">Choose file on the left</div>';
		}
		return $content;
	}

	function renderTableStruct(array $struct, array $local) {
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4 id="table-'.$table.'">Table: '.$table.'</h4>';

			$indexCompare = $this->compareTable($table, $local, $desc);
			$content .= new slTable($indexCompare, 'class="nospacing table table-striped"', array(
				'same' => 'Same',
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
				'Comment' => 'Comment',
				//'Index_comment' => 'Index_comment',
			));
		}
		return $content;
	}

	function convertFromOtherDB(array $desc) {
		if ($desc['tbl_name']) {    // SQLite
			$desc['Table'] = $desc['tbl_name'];
			unset($desc['tbl_name']);
			$desc['Key_name'] = $desc['name'];
			unset($desc['name']);
			$desc['Index_type'] = $desc['type'];
			unset($desc['type']);
			unset($desc['rootpage']);
			$desc['comment'] = $desc['sql'];
			unset($desc['sql']);
		}
		return $desc;
	}

	/**
	 * @param $table
	 * @param array $local
	 * @param $desc
	 * @return array
	 */
	protected function compareTable($table, array $local, $desc) {
		$indexCompare = array();
		foreach ($desc['indexes'] as $i => $index) {
			$index = $this->convertFromOtherDB($index);
			$localIndex = $local[$table]['indexes'][$i];
			$localIndex = $this->convertFromOtherDB($localIndex);
			//debug($index, $localIndex);

			unset($index['Cardinality'], $localIndex['Cardinality']);    // changes over time
			if ($index != $localIndex) {
				//$content .= getDebug($index, $localIndex);
				if (is_array($index)) {
					$indexCompare[] = array(
					'same'          => 'sql file',
					'###TR_MORE###' => 'style="background: pink"',
					) + $index;
				}
				if (is_array($localIndex)) {
					$indexCompare[] = array(
					'same'          => 'database',
					'###TR_MORE###' => 'style="background: pink"',
					) + $localIndex;
				} else {
					$indexCompare[] = array(
					'Table' => new HTMLTag('td', array(
					'colspan' => 10,
					), 'CREATE ' . ($index['Non_unique'] ? '' : 'UNIQUE') .
					' INDEX ' . $index['Key_name'] .
					' ON ' . $index['Table'] . ' (' . $index['Key_name'] . ')'
					),
					);
				}
			} else {
				//$content .= 'Same index: '.$index['Key_name'].' '.$localIndex['Key_name'].'<br />';
				$index['same'] = 'same';
				$index['###TR_MORE###'] = 'style="background: lightyellow"';
				$indexCompare[] = $index;
				//debug($index); exit();
			}
		}
		return $indexCompare;
	}

}
