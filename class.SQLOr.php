<?php

class SQLOr {

	protected $or = array();

	/**
	 * @var dbLayerPG
	 */
	protected $db;

	function __construct(array $ors) {
		$this->or = $ors;
		$this->db = Config::getInstance()->db;
	}

	function __toString() {
		/*$ors = array();
		foreach ($this->or as $or) {
			$ors[] = $this->db->getWherePart($or, false);
		}
		*/
		$ors = $this->db->quoteWhere($this->or);
		return '('.implode(' OR ', $ors).')';
	}

}
