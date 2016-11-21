<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWherePart {

	/**
	 * @var DBInterface
	 */
	protected $db;

	/**
	 * @var SQLBuilder
	 */
	protected $qb;

	protected $sql = '';

	/**
	 * @var string
	 */
	protected $field;

	function __construct($sql = '') {
		$this->sql = $sql;
		$this->db = Config::getInstance()->getDB();
		$this->qb = Config::getInstance()->getQb();
	}

	/**
	 * Not used directly
	 * @see SQLWhereEqual
	 * @return string
	 */
	function __toString() {
		//debug(__METHOD__, gettype2($this->db));
		if ($this->field && !is_numeric($this->field)) {
			if ($this->field == 'read') {
				//debug($this->field, $this->sql);
			}
			$part1 = $this->db->quoteWhere(
				array($this->field => $this->sql)
			);
			return implode('', $part1);
		} else {
			return $this->sql.'';
		}
	}

	function injectDB(DBInterface $db) {
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
	}

	function injectQB(SQLBuilder $qb) {
		$this->qb = $qb;
	}

	function injectField($field) {
		$this->field = $field;
	}

	function debug() {
		return $this->__toString();
	}

	/**
	 * Sub-classes should return their parameters
	 * @return null
	 */
	function getParameter() {
		return NULL;
	}

	function perform() {
		return $this->db->perform($this->__toString());
	}

}
