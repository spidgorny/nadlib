<?php

class SQLWhere {

	/**
	 * @var dbLayer|dbLayerPDO
	 */
	protected $db;

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
				if ($p instanceof SQLWherePart) {
					if (!is_numeric($field)) {
						$p->injectField($field);
					}
				} else {
					// bad: party = 'party = ''1'''
/*					$where = $this->db->quoteWhere(array(
						$field => $p,
					));
					$p = first($where);
*/
					$p = new SQLWhereEqual($field, $p);
					$p->injectDB($this->db);
				}
			}
			$sWhere = " WHERE\n\t".implode("\n\tAND ", $this->parts);	// __toString()

			// replace $1, $1, $1 with $1, $2, $3
			$params = $this->getParameters();
			//debug($sWhere, $params);
			foreach ($params as $i => $name) {
				$sWhere = str_replace_once('$0$', '$'.($i+1), $sWhere);
			}
			return $sWhere;
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

	function getParameters() {
		$parameters = array();
		foreach ($this->parts as $part) {
			if ($part instanceof SQLWherePart) {
				$plus = $part->getParameter();
				if ($plus) {
					//$parameters = array_merge($parameters, $plus);
					$parameters = array_merge($parameters, $plus);
				}
			}
		}
		return $parameters;
	}

}
