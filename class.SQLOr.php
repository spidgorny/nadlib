<?php

require_once('lib/dbLayer/class.SQLBuilder.php');

class SQLOr {
	
	protected $or = array();
	
	/**
	 * @var dbLayerPG
	 */
	protected $db;
	
	function __construct(array $ors) {
		$this->or = $ors;
		$this->db = $GLOBALS['db'];
	}
	
	function __toString() {
		$ors = array();
		//$qb = new SQLBuilder();
		foreach ($this->or as $or) {
			$ors[] = $this->db->getWherePart($or, false);
		}
		return '('.implode(' OR ', $ors).')';
	}
	
}