<?php

class AlterIndex extends AppControllerBE {

	/**
	 * @var string
	 */
	var $jsonFile;

	function __construct() {
		parent::__construct();
		$filename = $this->request->getFilename('file') ?: $this->db->database.'.json';
		$this->jsonFile = $this->config->appRoot.'/sql/'.$filename;

		if (false) {
			require_once $this->config->appRoot.'/constants.php';
			$GLOBALS['dbLayer'] = new dbLayerBL('buglog', PG_DB_LOGIN, PG_DB_PASSW, PG_DB_HOSTN);
			$this->db = $GLOBALS['dbLayer'];
			$this->config->db = $GLOBALS['dbLayer'];
			$this->config->qb->db = $GLOBALS['dbLayer'];
			$this->jsonFile = $this->config->appRoot.'/sql/buglog_dev.json';
		}

	}

	function sidebar() {
		$content = array();
		if (class_exists('AdminPage')) {
			$ap = new AdminPage();
			$content[] = $ap->sidebar();
		}

		$content[] = 'Schema: '.$this->db->getScheme().BR;
		$content[] = 'DB: '.$this->db->database.BR;
		$content[] = 'File: '.basename($this->jsonFile).BR;
		$content[] = $this->getActionButton('Save DB Struct', 'saveStruct');

		$li = array();
		$files = new ListFilesIn($this->config->appRoot.'/sql/');
		foreach ($files as $file) {
			/** @var $file SplFileInfo */
			if ($file->getExtension() == 'json') {
				$li[] = $this->a(new URL(get_class($this), array(
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
			//unset($struct['password']);	// debug
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
		$content = '';
		foreach ($struct as $table => $desc) {
			$content .= '<h4>Table: '.$table.'</h4>';

			$indexCompare = array();
			foreach ($desc['indexes'] as $i => $index) {
				$localIndex = $local[$table]['indexes'][$i];
				unset($index['Cardinality'], $localIndex['Cardinality']);	// changes over time
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
			$content .= new slTable($indexCompare, 'class="table"', array(
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
				//'Comment' => 'Comment',
				//'Index_comment' => 'Index_comment',
			));
		}
		return $content;
	}

}
