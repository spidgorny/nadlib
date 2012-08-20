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
		} else if ($this->qb->db instanceof dbLayer) {	// DCI
			$ors = array();
			foreach ($this->or as $or) {
				$ors[] = implode('', $this->qb->quoteWhere(array(
					$this->field => $or,
				)));
			}
		} else {										// MySQL
			$ors = $this->qb->quoteWhere($this->or);
		}
		$res = '('.implode(' OR ', $ors).')';
		//debug($this->or, $res);
		return $res;
	}

}
