<?php

/**
 * Base class for storing datasets or datarows or tabular data or set
 * or array of OODBase based objects.
 *
 */
class Collection {
	/**
	 *
	 * @var BijouDBConnector
	 */
	protected $db;
	protected $table = __CLASS__;
	var $idField = 'uid';
	var $parentID = NULL;
	protected $parentField = 'pid';

	/**
	 * @var ArrayPlus/array
	 */
	var $data = array();

	protected $thes = array(
		'uid' => 'ID',
		'title' => 'Title',
	);
	var $titleColumn = 'title';
	public $where = array();
	public $join = ''; // for LEFT OUTER JOIN queries
	/**
	 * Enter description here...
	 *
	 * @var Pager
	 */
	public $pager; // initialize if necessary with = new Pager(); in postInit()
	public $members = array();
	protected $orderBy = "ORDER BY uid";

	function __construct($pid = NULL, /*array/SQLWhere*/ $where = array(), $order = '') {
		$this->db = Config::getInstance()->db;
		$this->table = Config::getInstance()->prefixTable($this->table);
		$this->parentID = $pid;
		if (is_array($where)) {
			$this->where += $where;
		} else {
			$this->where = $where->addArray($this->where);
		}
		$this->orderBy = $order ? $order : $this->orderBy;
		$this->postInit();
		$this->retrieveDataFromDB();
		$this->preprocessData();
		$this->translateThes();
	}

	function postInit() {
		//$this->pager = new Pager();
	}

	function retrieveDataFromDB() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$query = $this->getQuery($this->where);
		//debug(__METHOD__, $query);
		$res = $this->db->perform($query);
		$data = $this->db->fetchAll($res);
		$this->data = ArrayPlus::create($data)->IDalize($this->idField);
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function getQuery(/*array*/ $where) {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if ($this->parentID) {
			$where[$this->parentField] = $this->parentID;
		}
		$qb = Config::getInstance()->qb;
		if ($where instanceof SQLWhere) {
			$query = $qb->getSelectQuerySW($this->table.' '.$this->join, $where, $this->orderBy, 'DISTINCT '.$this->table.'.*', TRUE);
		} else {
			$query = $qb->getSelectQuery($this->table.' '.$this->join, $where, $this->orderBy, 'DISTINCT '.$this->table.'.*', TRUE);
		}
		if ($this->pager) {
			$this->pager->initByQuery($query);
			$query .= $this->pager->getSQLLimit();
		}
		//debug($query);
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $query;
	}

	function preprocessData() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		//debug($this->data);
		$this->data = $this->data->getData();
		foreach ($this->data as $i => $row) { // Iterator by reference
			$this->data[$i] = $this->preprocessRow($row);
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function preprocessRow(array $row) {
		return $row;
	}

	function render() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$s = new slTable($this->data, 'class="nospacing" width="100%"');
		$s->thes = $this->thes;
		$content = $s->getContent();
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $content;
	}

	function getOptions() {
		$options = array();
		foreach ($this->data as $row) {
			$options[$row[$this->idField]] = $row[$this->titleColumn];
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
		$content = '<ul>';
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

	function translateThes() {
		// translate thes
		foreach ($this->thes as $key => &$trans) {
			if (is_string($trans) && $trans) {
				$trans = __($trans);
			}
		}
	}

	/**
	 * Will detect double-call and do nothing.
	 *
	 * @param unknown_type $class
	 */
	function objectify($class) {
		if (!$this->members) {
			foreach ($this->data as $row) {
				$key = $row[$this->idField];
				$this->members[$key] = new $class($row);
			}
		}
		return $this->members;
	}

	function __toString() {
		return $this->render().'';
	}

	/**
	 * Wrap output in <form> manually if necessary
	 */
	function addCheckboxes() {
		$this->thes = array('checked' => array(
			'name' => '<a href="javascript:void(0);" onclick="checkAll(this)">All</a><form method="POST">',
			'more' => 'align="right"',
		)) + $this->thes;
		$class = get_class($this);
		foreach ($this->data as &$row) {
			$id = $row[$this->idField];
			$checked = $_SESSION[$class][$id] ? 'checked' : '';
			$row['checked'] = '<input type="checkbox" name="'.$class.'['.$id.']" value="'.$id.'" '.$checked.' />';
		}
	}

}
