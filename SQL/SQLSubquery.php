<?php

/**
 * Class SQLSubquery
 * Currently only supports a single subquery replacing SQLFrom
 * TODO: Make it part of the SQLFrom like this
 * new SQLFrom(new SQLSubquery()) - this way we can have multiple subqueries
 */
class SQLSubquery extends SQLFrom {

	var $alias;

	var $parameters = [];

	function __construct(SQLSelectQuery $selectQuery, $alias) {
		parent::__construct($selectQuery);
		$this->alias = $alias;
	}

	function __toString() {
		return '('.$this->parts[0].') AS '.$this->alias;
	}

	function getParameters() {
		/** @var SQLSelectQuery $selectQuery */
		$selectQuery = $this->parts[0];
		if (is_object($selectQuery)) {
			return $selectQuery->getParameters();
		} else {
			return $this->parameters;
		}
	}

	function setParameters(array $p) {
		$this->parameters = $p;
	}

}
