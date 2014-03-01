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

	/**
	 * Source data from DB. Used in Localize.
	 * @var array
	 */
	protected $rows = array();

	function __construct($forceLang = NULL) {
		parent::__construct($forceLang);
        $config = Config::getInstance();
		$this->db = $config->db;
        $this->table = $config->prefixTable($this->table);
	}

	/**
	 * Why is it not called from the constructor?
	 * Because we need to specify the desired language $this->lang
	 */
	function init() {
		$this->rows = $this->readDB($this->lang);
		if ($this->rows) {
			$apRows = ArrayPlus::create($this->rows);
			$this->codeID = $apRows->column_assoc('code', 'id')->getData();
			$apRows = ArrayPlus::create($this->rows);
			$this->ll = $apRows->column_assoc('code', 'text')->getData();
		}
	}

	static function getInstance() {
		static $instance = NULL;
		if (!$instance) {
			$instance = new LocalLangDB();
		}
		return $instance;
	}

	/**
	 * Instead of searching if the original language (en) record exists
	 * it tries to insert and then catches the UNIQUE constraint exception.
	 * @param $text
	 */
	function saveMissingMessage($text) {
		//debug(__METHOD__, DEVELOPMENT, $text);
		if (DEVELOPMENT && $text) {
			$db = Config::getInstance()->db;
			try {
				$db->runInsertQuery($this->table, array(
					'code' => $text,
					'lang' => $this->defaultLang,	// is maybe wrong to save to the defaultLang?
					'text' => '',
					'page' => Request::getInstance()->getURL(),
				));
				//debug($db->lastQuery, $db->affectedRows());
				$this->ll[$text] = $text;
				$this->codeID[$text] = $db->lastInsertID();
			} catch (Exception $e) {
				// ignore
			}
		}
	}

	/**
	 * Dangerous - may overwrite
	 * @param array $data
	 */
	function updateMessage(array $data) {
		debug_pre_print_backtrace();
		exit();
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
				$rows = ArrayPlus::create($rows)->IDalize('id')->getData();
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

	function getRow($id) {
		return $this->rows[$id];
	}

}
