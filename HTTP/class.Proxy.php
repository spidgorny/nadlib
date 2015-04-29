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
		$this->db = Config::getInstance()->getDB();
		$this->ratio = $this->data['ok']/max(1, $this->data['fail']);
	}

	static function getRandomOrBest($percentRandom = 50) {
		$db = Config::getInstance()->getDB();
		/** @var AppController $c */
		$c = Index::getInstance()->controller;
		if (rand(0, 100) < $percentRandom) { // 25%
			$row = $db->fetchSelectQuery('proxy', array('fail' => new AsIs('< ').self::$maxFail),
				'ORDER BY rand() LIMIT 1');
			if ($row[0]) {
				$proxy = new Proxy($row[0]);
				$c->log('Random proxy: '.$proxy.' (ratio: '.$proxy->ratio.')', __METHOD__, 0);
			} else {
				$c->log('No proxy', __METHOD__);
			}
		} else {
			$best = self::getBest();
			$idx = rand(0, sizeof($best)-1);
			$proxy = new Proxy($best[$idx]);
			$c->log('Best proxy ('.$idx.'): '.$proxy.' (ratio: '.$proxy->ratio.')', __METHOD__);
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
		if (!self::$best) {
			$db = Config::getInstance()->getDB();
			$rows = $db->fetchSelectQuery('proxy', array(
					'fail' => new AsIsOp('< ' . self::$maxFailBest),
					//'ok' => new AsIs('> 0'),
				), '
			/*ORDER BY ok DESC, fail ASC*/
			ORDER BY ratio DESC
			LIMIT ' . $limit, ', ok/fail AS ratio');
			//debug($rows);
			self::$best = $rows;
		}
		return self::$best;
	}

	/**
	 * @return array(342571/359601)
	 */
	static function getProxies() {
		$db = Config::getInstance()->getDB();
		$row = $db->fetchSelectQuery('proxy', array(), '', 'count(*)');	// total
		$p = new Proxy();
		$okProxy = $p->getOKcount();
		return array($okProxy, $row[0]['count(*)']);
	}

	function getOKcount() {
		$rowOK = $this->db->fetchSelectQuery('proxy', array(
			'fail' => new AsIsOp('< '.self::$maxFail)
		), '', 'count(*)');
		return $rowOK[0]['count(*)'];
	}

	function fail() {
		$this->update(array('fail' => $this->data['fail']+1));
	}

	function ok() {
		$this->update(array('ok' => $this->data['ok']+1));
	}

}
