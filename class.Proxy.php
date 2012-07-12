<?php

class Proxy extends OODBase {
	protected $table = 'proxy';
	protected $titleColumn = 'proxy';
	static $best = array();
	protected static $maxFail = 11;
	protected static $maxFailBest = 150;

	static function getRandom() {
		if (rand(0, 100) > 75) { // 25%
			$db = MySQL::getInstance();
			$row = $db->fetchSelectQuery('proxy', array('fail' => new AsIs('< ').self::$maxFail),
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
		return $proxy;
	}

	function __toString() {
		return $this->data['proxy'].'';
	}

	static function getList() {
		$db = MySQL::getInstance();
		$rows = $db->fetchSelectQuery('proxy', array(), 'ORDER BY ok DESC, fail ASC LIMIT 100');
		return $rows;
	}

	function getBest($limit = 100) {
		//if (!self::$best) {
			$db = MySQL::getInstance();
			$rows = $db->fetchSelectQuery('proxy', array(
				'fail' => new AsIs('< '.self::$maxFailBest),
				//'ok' => new AsIs('> 0'),
			), 'ORDER BY ok DESC, fail ASC LIMIT '.$limit);
			self::$best = $rows;
		//}
		return self::$best;
	}

	function getOKcount() {
		$db = MySQL::getInstance();
		$rowOK = $db->fetchSelectQuery('proxy', array('fail' => new AsIs('< '.self::$maxFail)), '', 'count(*)', TRUE);
		return $rowOK[0]['count(*)'];
	}

}
