<?php

class SQLOr extends SQLWherePart {

	protected $or = array();

	/**
	 * @var dbLayerPG
	 */
	protected $db;

	function __construct(array $ors) {
		//parent::__construct();
		$this->or = $ors;
		$this->db = Config::getInstance()->db;
	}

	function __toString() {
		if (!$this->qb) {
			//$di = new DIContainer();
			//$di->db = $this->db;
			$this->qb = Config::getInstance()->qb;
		}
		if ($this->qb->db instanceof dbLayerPG) {		// ???
			$ors = array();
			foreach ($this->or as $or) {
				$ors[] = $this->db->getWherePart($or, false);
			}
		} else if ($this->qb->db instanceof dbLayer) {	// DCI, ORS
			// where is it used? in ORS for sure, but make sure you don't call new SQLOr(array('a', 'b', 'c'))
			// http://ors.nintendo.de/NotifyVersion
			if ($this->field) {
				$ors = array();
				foreach ($this->or as $field => $or) {
					$tmp = $this->qb->quoteWhere(
						array($this->field => $or)
						//$or
					);
					$ors[] = implode('', $tmp);
				}
			} else {
				foreach ($this->or as $field => $p) {
					if ($p instanceof SQLWherePart) {
						$p->injectField($field);
					}
				}
				$ors = $this->qb->quoteWhere($this->or);
			}
		} else {										// MySQL
			$ors = $this->qb->quoteWhere($this->or);
		}
		if ($ors) {
			$res = '('.implode(' OR ', $ors).')';
		} else {
			$res = '/* EMPTY OR */';
		}
		//debug($this, $ors, $res);
		return $res;
	}

	function debug() {
		return array($this->field => $this->or);
	}

}
