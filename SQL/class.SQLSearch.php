<?php

class SQLSearch {

	/**
	 * @var string table name
	 */
	protected $table;

	/**
	 * @var string search string
	 */
	protected $sword;

	/**
	 * Search string split into words
	 * @var array
	 */
	protected $words = array();

	/**
	 * Update it from outside to search different columns
	 * @var array
	 */
	public $searchableFields = array(
		'title',
	);

	/**
	 * Not used
	 * @var string
	 */
	public $queryJoins = '';

	/**
	 * Replace with ILIKE if necessary
	 * @var string
	 */
	public $likeOperator = 'LIKE';

	function __construct($table, $sword) {
		//debug(array($table, $sword));
		$this->table = $table;
		$this->sword = $sword;
		$this->words = $this->getSplitWords($this->sword);
		//debug($this->words);
	}

	function getSplitWords($sword) {
		$sword = trim($sword);
		$words = explode(' ', $sword . ' ' . $GLOBALS['i']->user->data['searchAppend']);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	function __toString() {
		$where = $this->getWhere();
		//$query = str_replace('WHERE', $this->queryJoins.' WHERE', $query);
		$query = '';
		if ($where) {
			$qb = Config::getInstance()->qb;
			$whereString = $qb->quoteWhere($where);
			$query .= implode(' AND ', $whereString);
		}
		return $query;
	}

	public function getWhere() {
		$query = '';
		$where = array();
		$words = $this->words;
		//debug($words);
		foreach ($words as $word) {
			//if (!$i) {
			if (FALSE) {
				$query .= $this->getSearchSubquery($word);
				//$query .= '( '.$this->getSearchSubquery($word).')';
				//$query .= ' AS score_'.$i;
			} else {
				if ($word{0} == '!') {
					$word = substr($word, 1);
					$where[] = $this->table.'.id NOT IN ( '.$this->getSearchSubquery($word, $this->table.'.id').') ';
				} else {
					//$this->queryJoins .= ' INNER JOIN ( '.$this->getSearchSubquery($word).') AS score_'.$i.' USING (id) ';
					// join has problem: #1060 - Duplicate column name 'id' in count(*) from (select...)
					$where[] = $this->table.'.id IN ( '.$this->getSearchSubquery($word, $this->table.'.id').') ';
				}
			}
		}
		return $where;
	}

	protected function getSearchSubquery($word, $select = NULL) {
		$table = $this->table;
		$select = new SQLSelect($select ? $select : 'DISTINCT *');
		$from = new SQLFrom($table);
		$where = new SQLWhere($where);
		$query = new SQLSelectQuery($select, $from, $where, NULL, NULL, NULL, new SQLOrder('id'));
		//$query->setJoin(new SQLJoin("LEFT OUTER JOIN tag ON (tag.id_score = ".$this->table.".id)"));

		//$query->where->add($this->getSearchWhere($word, $i ? 'score_'.$i : $table));
		$query->where->add($this->getSearchWhere($word)); // please put the table prefix into $this->searchableFields
		return $query;
	}

	protected function getSearchWhere($word, $prefix = '') {
		if ($word{0} == '!') {
			$like = 'NOT ' . $this->likeOperator;
			$or = "\n\t\tAND";
		} else {
			$like = $this->likeOperator;
			$or = "\n\t\tOR";
		}

		$prefix = $prefix ? $prefix.'.' : '';

		foreach ($this->searchableFields as $field) {
			$part[] = "{$prefix}{$field} {$like} '%$1%'";
		}
		$part = implode(' ' . $or . ' ', $part);
		$part = str_replace('$1', $word, $part);
		$part = str_replace("\r\n", "\n", $part);

		// test if it's a date
		$date1 = strtotime($word);
		if ($date1 > 0) {
			$date2 = strtotime('+1 day', $date1);
			$date1 = date('Y-m-d', $date1);
			$date2 = date('Y-m-d', $date2);
			$part .= "
				$or $prefix.ctime BETWEEN '$date1' AND '$date2'
			";
		}

		return '('.$part.')'; // because of OR
	}

}
