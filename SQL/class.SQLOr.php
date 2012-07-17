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
		if ($this->qb->db instanceof dbLayer) {
			$ors = array();
			foreach ($this->or as $or) {
				$ors[] = $this->qb->getWherePart($or, false);
			}
		} else {
			$ors = $this->qb->quoteWhere($this->or);
		}
		$res = '('.implode(' OR ', $ors).')';
		//debug($this->or, $res);
		return $res;
	}

}
