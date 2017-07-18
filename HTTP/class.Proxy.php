<?php

class Proxy extends OODBase {
	public $table = 'proxy';
	protected $titleColumn = 'proxy';

	static $best = array();

	protected static $maxFail = 15;
	protected static $maxFailBest = 200;

	/**
	 * @var MySQL
	 */
	protected $db;

	public $ratio = 0;

	function __construct($row = NULL) {
		parent::__construct($row);
		$this->db = Config::getInstance()->db;
		$this->ratio = $this->data['ok']/max(1, $this->data['fail']);
	}

	static function getRandom() {
		$db = Config::getInstance()->db;
		$c = Index::getInstance()->controller;
		if (rand(0, 100) > 75) { // 25%
			$row = $db->fetchSelectQuery('proxy', array('fail' => new AsIs('< ').self::$maxFail),
				'ORDER BY rand() LIMIT 1');
			if ($row[0]) {
				$proxy = new Proxy($row[0]);
				$c->log('Random proxy: '.$proxy.' (ratio: '.$proxy->ratio.')', __CLASS__, 0);
			} else {
				$c->log('No proxy', __CLASS__);
			}
		} else {
			$best = self::getBest();
			$idx = rand(0, sizeof($best));
			$proxy = new Proxy($best[$idx]);
			$c->log('Best proxy ('.$idx.'): '.$proxy.' (ratio: '.$proxy->ratio.')', __CLASS__);
		}
		return $proxy;
	}

	function __toString() {
		return $this->data['proxy'].'';
	}

	function getList() {
		$rows = $this->db->fetchSelectQuery('proxy', array(), 'ORDER BY ok DESC, fail ASC LIMIT 100');
		return $rows;
	}

	static function getBest($limit = 100) {
		$db = Config::getInstance()->db;
		$rows = $db->fetchSelectQuery('proxy', array(
			'fail' => new AsIsOp('< '.self::$maxFailBest),
			//'ok' => new AsIs('> 0'),
		), '
		/*ORDER BY ok DESC, fail ASC*/
		ORDER BY ratio DESC
		LIMIT '.$limit, ', ok/fail AS ratio');
		//debug($rows);
		self::$best = $rows;
		return self::$best;
	}

	/**
	 * @return array(342571/359601)
	 */
	static function getProxies() {
		$db = Config::getInstance()->db;
		$row = $db->fetchSelectQuery('proxy', array(), '', 'count(*)', TRUE);	// total
		$p = new Proxy();
		$okProxy = $p->getOKcount();
		return array($okProxy, $row[0]['count(*)']);
	}

	function getOKcount() {
		$rowOK = $this->db->fetchSelectQuery('proxy', array(
			'fail' => new AsIsOp('< '.self::$maxFail)
		), '', 'count(*)', TRUE);
		return $rowOK[0]['count(*)'];
	}

	function fail() {
		$this->update(array('fail' => $this->data['fail']+1));
	}

	function ok() {
		$this->update(array('ok' => $this->data['ok']+1));
	}

}
