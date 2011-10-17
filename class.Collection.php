<?php

/**
 * Base class for storing datasets or datarows or tabular data or set or array of OODBase based objects.
 *
 */

class Collection {
	protected $db;
	protected $table = __CLASS__;
	var $parentID = NULL;
	protected $parentField = 'pid';
	protected $idField = 'id';
	var $data = array();
	protected $thes = array();
	var $titleColumn = 'title';
	public $where = array();
	public $join = ''; // for LEFT OUTER JOIN queries
	/**
	 * Enter description here...
	 *
	 * @var Pager
	 */
	protected $pager; // initialize if necessary with = new Pager();
	public $members = array();
	protected $orderby = 'id';

	function __construct($pid = NULL, array $where = array()) {
		$this->db = $GLOBALS['i']->db;
		$this->parentID = $pid;
		$this->where = $where;
		$this->retrieveDataFromDB();
		$this->preprocessData();
	}

	function retrieveDataFromDB() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$query = $this->getQuery($this->where);
		//debug($query, TRUE);
		$res = $this->db->perform($query);
		$data = $this->db->fetchAll($res);
		$this->data = ArrayPlus::create($data)->IDalize($this->idField);
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function getQuery(array $where) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if ($this->parentID) {
			$where[$this->parentField] = $this->parentID;
		}
		$qb = Config::getInstance()->qb;
		$query = $qb->getSelectQuery($this->table.' '.$this->join, $where, "ORDER BY ".$this->orderby, 'DISTINCT '.$this->table.'.*', TRUE);
		if ($this->pager) {
			$this->pager->initByQuery($query);
			$query .= $this->pager->getSQLLimit();
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $query;
	}

	function preprocessData() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		foreach ($this->data as &$row) {
			$row = $this->preprocessRow($row);
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function preprocessRow(array $row) {
		return $row;
	}

	function render() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$s = new slTable();
		$s->data = $this->data;
		$s->thes = $this->thes;
		$content .= $s->getContent();
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $content;
	}

	function getOptions() {
		$options = array();
		foreach ($this->data as $row) {
			$options[$row['id']] = $row[$this->titleColumn];
		}
		return $options;
	}

	function findInData(array $where) {
		foreach ($this->data as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				return $row;
			}
		}
	}

	function renderList() {
		$content .= '<ul>';
		foreach ($this->data as $row) {
			$content .= '<li>';
			foreach ($this->thes as $key => $_) {
				$content .= $row[$key]. ' ';
			}
			$content .= '</li>';
		}
		$content .= '</ul>';
		return $content;
	}

	function objectify($class) {
		$this->members = array(); // cleaning as this could be clone copy
		foreach ($this->data as $row) {
			$key = $row[$this->idField];
			$this->members[$key] = new $class($row);
		}
	}

}
