<?php

/**
 * Singleton
 *
 */
class LocalLangDB extends LocalLang {
	public    $indicateUntranslated = false;

	function __construct($forceLang = NULL) {
		parent::__construct($forceLang);
		$rows = $this->readDB($this->lang);
		if ($rows) {
			$this->codeID = ArrayPlus::create($rows)->column_assoc('code', 'id')->getData();
			$this->ll = ArrayPlus::create($rows)->column_assoc('code', 'text')->getData();
		}
	}

	static function getInstance() {
		static $instance = NULL;
		if (!$instance) {
			$instance = new LocalLangDB();
		}
		return $instance;
	}

	function saveMissingMessage($text) {
		if (DEVELOPMENT && $text) {
			$db = Config::getInstance()->db;
			$db->runInsertQuery('app_interface', array(
				'code' => $text,
				'lang' => $this->lang,
				'text' => $text,
			));
			$this->ll[$text] = $text;
		}
	}

	function readDB($lang) {
		//try {
			$db = Config::getInstance()->db;
			$res = $db->getTableColumns('app_interface');
			if ($res) {
				$rows = $db->fetchSelectQuery('app_interface', array(
					'lang' => $lang,
				), 'ORDER BY id');
			} else {
				throw new Exception('No translation found in DB');
			}
		//} catch (Exception $e) {
			// read from DB failed, continue
			//throw new Exception('Reading locallang from DB failed.');
			// throwing exception leads to making a new instance of LocalLang and it masks DB error
		//}
		return $rows;
	}

}
