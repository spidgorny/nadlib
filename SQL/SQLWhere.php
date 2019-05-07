<?php

class SQLWhere
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $parts = array();

	function __construct($where = NULL)
	{
		if (is_array($where)) {
			$this->parts = $where;
		} elseif ($where) {
			$this->add($where);
		}
		$this->db = Config::getInstance()->getDB();
	}

	function injectDB(DBInterface $db)
	{
		//debug(__METHOD__, gettype2($db));
		$this->db = $db;
	}

	function add($where, $key = NULL)
	{
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

	function addArray(array $where)
	{
		foreach ($where as $key => $el) {
			$this->add($el, $key);
		}
		return $this;
	}

	function __toString()
	{
		if ($this->parts) {
//			debug($this->parts);
			foreach ($this->parts as $field => &$p) {
				if ($field == 'read') {
					//debug($field, gettype2($p), $p instanceof SQLWherePart);
				}
				if ($p instanceof SQLWherePart) {
					$p->injectDB($this->db);
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
			$sWhere = " WHERE\n\t" . implode("\n\tAND ", $this->parts);    // __toString()

			$sWhere = $this->replaceParams($sWhere);
			return $sWhere;
		} else {
			return '';
		}
	}

	function replaceParams($sWhere)
	{
		// replace $0$, $0$, $0$ with $1, $2, $3
		$params = $this->getParameters();
		//debug($sWhere, $params);
		foreach ($params as $i => $name) {
			if ($this->db->isMySQL()) {
				$sWhere = str_replace_once('$0$', '?', $sWhere);
			} else {
				$sWhere = str_replace_once('$0$', '$' . ($i + 1), $sWhere);
			}
		}
		return $sWhere;
	}

	/**
	 * @return array
	 */
	function getAsArray()
	{
		return $this->parts;
	}

	function debug()
	{
		return $this->parts;
	}

	static function genFromArray(array $where)
	{
		foreach ($where as $key => &$val) {
			if (!($val instanceof SQLWherePart)) {
				$val = new SQLWhereEqual($key, $val);
			}
		}
		return new self($where);
	}

	function getParameters()
	{
		$parameters = array();
		foreach ($this->parts as $part) {
			if ($part instanceof SQLWherePart) {
				$plus = $part->getParameter();
//				debug(gettype2($part), $part->getField(), $plus);
				if (is_array($plus)) {
					$parameters = array_merge($parameters, $plus);
				} elseif (!is_null($plus)) {
					// add even if empty string or 0
					$parameters[] = $plus;
				}
			}
		}
//		debug($parameters);
		return $parameters;
	}

}
