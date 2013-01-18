<?php

/**
 * Singleton
 *
 */
class LocalLangDB extends LocalLang {
	public $indicateUntranslated = false;
	public $table = 'interface';

	/**
	 * @var MySQL
	 */
	protected $db;

	function __construct($forceLang = NULL) {
		parent::__construct($forceLang);
		$this->db = Config::getInstance()->db;
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
			$db->runInsertQuery($this->table, array(
				'code' => $text,
				'lang' => $this->lang,
				'text' => $text,
			));
			$this->ll[$text] = $text;
		}
	}

	function updateMessage(array $data) {
		$llm = new LocalLangModel($data['lang'], $data['code']);
		if ($llm->id) {
			$llm->update(array(
				'text' => $data['text'],
			));
		} else {
			$llm->insert($data);
		}
	}

	function readDB($lang) {
		//try {
			$res = $this->db->getTableColumns($this->table);
			if ($res) {
				$rows = $this->db->fetchSelectQuery($this->table, array(
					'lang' => $lang,
				), 'ORDER BY id');
			} else {
				debug($this->db->lastQuery);
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
