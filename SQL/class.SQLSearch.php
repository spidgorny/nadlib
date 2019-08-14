<?php

class SQLSearch
{
	protected $table;
	protected $sword;
	protected $words = array();
	public $searchableFields = array(
		'title',
	);

	/**
	 * @var DBInterface
	 */
	protected $db;

	function __construct($table, $sword)
	{
		//debug(array($table, $sword));
		$this->table = $table;
		$this->sword = $sword;
		$this->words = $this->getSplitWords($this->sword);
		//debug($this->words);
		$this->db = Config::getInstance()->db;
	}

	function getSplitWords($sword)
	{
		$sword = trim($sword);
		$words = explode(' ', $sword . ' ' . $GLOBALS['i']->user->data['searchAppend']);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	function __toString()
	{
		$where = $this->getWhere();
		//$query = str_replace('WHERE', $queryJoins.' WHERE', $query);
		$query = '';
		if ($where) {
			$whereString = $this->qb->quoteWhere($where);
			$query .= implode(' AND ', $whereString);
		}
		return $query;
	}

	/**
	 * @return array
	 */
	public function getWhere()
	{
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
					$where[] = $this->table . '.id NOT IN ( ' . $this->getSearchSubquery($word, $this->table . '.id') . ') ';
				} else {
					//$queryJoins .= ' INNER JOIN ( '.$this->getSearchSubquery($word).') AS score_'.$i.' USING (id) ';
					// join has problem: #1060 - Duplicate column name 'id' in count(*) from (select...)
					$where[] = $this->table . '.id IN ( ' . $this->getSearchSubquery($word, $this->table . '.id') . ') ';
				}
			}
		}
		return $where;
	}

	function getSearchSubquery($word, $select = NULL)
	{
		$table = $this->table;
		$select = new SQLSelect($select ? $select : 'DISTINCT *');
		$from = new SQLFrom($table);
		$where = new SQLWhere(array());
		$query = new SQLSelectQuery($select, $from, $where, NULL, NULL, NULL, new SQLOrder('id'));
		//$query->setJoin(new SQLJoin("LEFT OUTER JOIN tag ON (tag.id_score = ".$this->table.".id)"));

		//$query->where->add($this->getSearchWhere($word, $i ? 'score_'.$i : $table));
		$query->where->add($this->getSearchWhere($word)); // please put the table prefix into $this->searchableFields
		return $query;
	}

	function getSearchWhere($word, $prefix = '')
	{
		if ($word{0} == '!') {
			$like = 'NOT LIKE';
			$or = "\n\t\tAND";
		} else {
			$like = 'LIKE';
			$or = "\n\t\tOR";
		}

		$prefix = $prefix ? $prefix . '.' : '';

		foreach ($this->searchableFields as $field) {
			$part[] = "{$prefix}{$field} {$like} '%$1%'";
		}
		$part = implode(' ' . $or . ' ', $part);
		$part = str_replace('$1', $this->db->escape($word), $part);
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

		return '(' . $part . ')'; // because of OR
	}

}
