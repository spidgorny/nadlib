<?php

/**
 * Singleton
 */
class LocalLangDB extends LocalLang {

	public $table = 'interface';

	/**
	 * @var MySQL|dbLayerBase
	 */
	protected $db;

	/**
	 * Source data from DB. Used in Localize.
	 * @var array
	 */
	protected $rows = array();

	public $saveMissingMessages = true;

	function __construct($forceLang = NULL) {
		parent::__construct($forceLang);
	}

	/**
	 * Why is it not called from the constructor?
	 * Because we need to specify the desired language $this->lang
	 */
	function init() {
		$config = Config::getInstance();
		$this->db = $config->db;
		$this->table = $config->prefixTable($this->table);
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
			$instance = new static();
			$instance->init();
		}
		return $instance;
	}

	/**
	 * Instead of searching if the original language (en) record exists
	 * it tries to insert and then catches the UNIQUE constraint exception.
	 * @param $code
	 */
	function saveMissingMessage($code) {
		nodebug(array(
			'object' => spl_object_hash($this),
			'method' => __METHOD__,
			'DEVELOPMENT' => DEVELOPMENT,
			'code' => $code,
			'$this->ll[code]' => ifsetor($this->ll[$code]),
			));
		if (DEVELOPMENT && $code && $this->saveMissingMessages && $this->db) {
			try {
				$where = array(
					'code' => $code,
					'lang' => $this->defaultLang,		// is maybe wrong to save to the defaultLang?
				);
				$insert = array(
					'text' => '',
					'page' => Request::getInstance()->getURL(),
				);
				$cols = $this->db->getTableColumns($this->table);
				if (ifsetor($cols['cuser'])) {
					$insert['cuser'] = Config::getInstance()->user->id;
				}
				if (ifsetor($cols['muser'])) {
					$insert['muser'] = Config::getInstance()->user->id;
				}
				$res = $this->db->runInsertNew($this->table, $where, $insert);
				//debug($db->lastQuery, $db->affectedRows());
				$this->ll[$code] = $code;
				$this->codeID[$code] = $this->db->lastInsertID($res);
				//debug($this->db->lastQuery);
			} catch (Exception $e) {
				Index::getInstance()->log(__METHOD__, $e->getMessage());
			}
		}
	}

	/**
	 * Dangerous - may overwrite
	 * @param array $data
	 * @throws AccessDeniedException
	 * @throws Exception
	 */
	function updateMessage(array $data) {
		if (Config::getInstance()->user->isAdmin()) {
			$llm = new LocalLangModel($data['lang'], $data['code']);
			if ($llm->id) {
				$llm->update(array(
					'text' => $data['text'],
				));
			} else {
				$llm->insert($data);
			}
		} else {
			throw new AccessDeniedException();
		}
	}

	function readDB($lang) {
		$res = $this->db->getTableColumnsEx($this->table);
		if ($res) {
			// wrong query
			/*$rows = $this->db->fetchSelectQuery($this->table.
				" AS a RIGHT OUTER JOIN ".$this->table." AS en
				ON ((a.code = en.code OR a.code IS NULL) AND en.lang = 'en')", array(
				'a.lang' => new SQLAnd(array(
					'a.lang' => new SQLWhereEqual('a.lang', $lang),
					'a.lang ' => new SQLWhereEqual('a.lang', NULL),
					)
				),
				'a.lang' => $lang,
				'en.lang' => 'en',
			), 'ORDER BY id',
				'coalesce(a.id, en.id) AS id,
				coalesce(a.code, en.code) AS code,
				coalesce(a.lang, en.lang) AS lang,
				coalesce(a.text, en.text) AS text,
				a.page');
			debug($this->db->lastQuery, sizeof($rows), first($rows));*/
			$rows = $this->db->fetchSelectQuery($this->table, array(
				'lang' => $lang,
			), 'ORDER BY id');
			$rows = ArrayPlus::create($rows)->IDalize('id')->getData();
		} else {
			debug($this->db->lastQuery);
			throw new Exception('No translation found in DB');
		}
		return $rows;
	}

	function getRow($id) {
		return $this->rows[$id];
	}

	function showLangSelection() {
		$content = '';
		$stats = $this->getLangStats();
		if (sizeof($stats) > 1) {           // don't show selection of just one language
			foreach ($stats as $row) {
				$u = URL::getCurrent();
				$u->setParam('setLangCookie', $row['lang']);
				$title = $row['lang'] . ' (' . $row['percent'] . ')';
				$content .= '<a href="' . $u->buildURL() . '" title="' . $title . '">
					<img src="img/' . $row['lang'] . '.gif" width="20" height="12" />
				</a>';
			}
		}
		//debug($_SERVER['REQUEST_URI'], $u, $u->buildURL());
		return $content;
	}

	function getLangStats() {
		$en = $this->readDB('en');
		$countEN = sizeof($en) ? sizeof($en) : 1;
		$langs = array_combine($this->possibleLangs, $this->possibleLangs);
		foreach ($langs as &$lang) {
			$rows = $this->readDB($lang);
			$lang = array(
				'img' => new htmlString('<img src="img/'.$lang.'.gif" width="20" height="12" />'),
				'lang' => $lang,
				'rows' => sizeof($rows),
				'percent' => number_format(sizeof($rows)/$countEN*100, 0).'%',
			);
		}
		return $langs;
	}

}
