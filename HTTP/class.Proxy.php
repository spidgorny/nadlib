<?php

class Proxy extends OODBase {
	public $table = 'proxy';
	protected $titleColumn = 'proxy';
	static $best = array();
	protected static $maxFail = 11;
	protected static $maxFailBest = 150;
	protected $db;

	function __construct() {
		$this->db = Config::getInstance()->db;
	}

	function getRandom() {
		if ($this->db) {
			if (rand(0, 100) > 75) { // 25%
				$row = $this->db->fetchSelectQuery('proxy', array('fail' => new AsIs('< ').self::$maxFail),
					'ORDER BY rand() LIMIT 1');
				if ($row[0]) {
					$proxy = new Proxy($row[0]);
					Controller::log('Random proxy: '.$proxy.' (success/fail: '.$proxy->data['ok'].'/'.$proxy->data['fail'].')', __CLASS__, 0);
				} else {
					Controller::log('No proxy', __CLASS__);
				}
			} else {
				$best = self::getBest();
				$idx = rand(0, sizeof($best));
				$proxy = new Proxy($best[$idx]);
				Controller::log('Best proxy ('.$idx.'): '.$proxy.' (success/fail: '.$proxy->data['ok'].'/'.$proxy->data['fail'].')', __CLASS__);
			}
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

	function getBest($limit = 100) {
		//if (!self::$best) {
			$rows = $this->db->fetchSelectQuery('proxy', array(
				'fail' => new AsIs('< '.self::$maxFailBest),
				//'ok' => new AsIs('> 0'),
			), 'ORDER BY ok DESC, fail ASC LIMIT '.$limit);
			self::$best = $rows;
		//}
		return self::$best;
	}

	function getOKcount() {
		$rowOK = $this->db->fetchSelectQuery('proxy', array('fail' => new AsIs('< '.self::$maxFail)), '', 'count(*)', TRUE);
		return $rowOK[0]['count(*)'];
	}

}
