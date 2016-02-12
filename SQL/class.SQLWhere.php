<?php

class SQLWhere {

	protected $parts = array();

	function __construct($where = NULL) {
		if (is_array($where)) {
			$this->parts = $where;
		} elseif ($where) {
			$this->add($where);
		}
	}

	function add($where, $key = NULL) {
		if (is_array($where)) {
			//debug($where);
			throw new InvalidArgumentException(__METHOD__);
		}
		if (!$key || is_numeric($key)) {
			$this->parts[] = $where;
		} else {
			$this->parts[$key] = $where;
		}
	}

	function addArray(array $where) {
		foreach ($where as $key => $el) {
			$this->add($el, $key);
		}
		return $this;
	}

	function __toString() {
		if ($this->parts) {
			foreach ($this->parts as $field => &$p) {
				if ($p instanceof SQLWherePart && !is_numeric($field)) {
					$p->injectField($field);
				} else {
					$db = Config::getInstance()->getDB();
					$where = $db->quoteWhere([
						$field => $p,
					]);
					$p = first($where);
				}
			}
			return " WHERE\n\t".implode("\n\tAND ", $this->parts);	// __toString()
		} else {
			return '';
		}
	}

	/**
	 * @return array
	 */
	function getAsArray() {
		return $this->parts;
	}

	function debug() {
		return $this->parts;
	}

	static function genFromArray(array $where) {
		foreach ($where as $key => &$val) {
			$val = new SQLWhereEqual($key, $val);
		}
		return new SQLWhere($where);
	}

}
