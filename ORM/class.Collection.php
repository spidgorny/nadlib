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
	public $db;
	protected $table = __CLASS__;
	var $idField = 'uid';
	var $parentID = NULL;
	protected $parentField = 'pid';

	/**
	 * @var ArrayPlus/array
	 */
	var $data = array();

	public $thes;
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
	protected $orderBy = "uid";
	public $query;

	protected $request;

	public $useSorting = true;

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
		$this->request = Request::getInstance();
		$this->postInit();

		// should be dealt with by the Controller
		/*$sortBy = $this->request->getSubRequest('slTable')->getCoalesce('sortBy', $this->orderBy);
		if ($this->thes && is_array($this->thes[$sortBy]) && $this->thes[$sortBy]['source']) {
			$sortBy = $this->thes[$sortBy]['source'];
		}
		$sortOrder = $this->request->getSubRequest('slTable')->getBool('sortOrder') ? 'DESC' : 'ASC';
		$this->orderBy = 'ORDER BY '.$sortBy.' '.$sortOrder;*/

		$this->retrieveDataFromDB();
		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : array('name' => $val);
		}
		$this->translateThes();
		//$GLOBALS['HTMLFOOTER']['jquery.infinitescroll.min.js'] = '<script src="js/jquery.infinitescroll.min.js"></script>';
	}

	function postInit() {
		//$this->pager = new Pager();
	}

	/**
	 * -1 will prevent data retrieval
	 */
	function retrieveDataFromDB() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if (!$this->parentID || $this->parentID > 0) {
			$this->query = $this->getQuery($this->where);
			$res = $this->db->perform($this->query);
			$data = $this->db->fetchAll($res);
			$this->data = ArrayPlus::create($data)->IDalize($this->idField)->getData();
			$this->preprocessData();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function getQuery(/*array*/ $where) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
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
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $query;
	}

	function preprocessData() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		foreach ($this->data as &$row) { // Iterator by reference
			$row = $this->preprocessRow($row);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function preprocessRow(array $row) {
		return $row;
	}

	function render() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if ($this->data) {
			$this->prepareRender();
			$url = new URL();
			if ($this->pager) {
				$pages = $this->pager->renderPageSelectors($url);

				$ps = new PageSize();
				$ps->setURL($url);
				$pages .= $ps->render();
			}
			$s = new slTable($this->data, 'class="nospacing" width="100%" id="'.get_class($this).'"');
			$s->thes = $this->thes;
			$s->sortable = $this->useSorting;
			$s->sortLinkPrefix = new URL();
			$content = $pages . $s->getContent() . $pages;
		} else {
			$content = '<div class="message">No data</div>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $content;
	}

	function prepareRender() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		foreach ($this->data as &$row) { // Iterator by reference
			$row = $this->prepareRenderRow($row);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function prepareRenderRow(array $row) {
		return $row;
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

	function renderMembers() {
		$content = '';
		foreach ($this->members as $obj) {
			$content .= $obj."\n";
		}
		return $content;
	}

	function translateThes() {
		// translate thes
		if (is_array($this->thes)) foreach ($this->thes as &$trans) {
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

	function showFilter() {
		if ($this->filter) {
			$f = new HTMLFormTable();
			$this->filter = $f->fillValues($this->filter, $this->request->getAll());
			$f->showForm($this->filter);
			$f->submit('Filter', '', array('class' => 'btn-primary'));
			$content = $f->getContent();
		}
		return $content;
	}

	function getFilterWhere() {
		$where = array();
		if ($this->filter) {
			foreach ($this->filter as $field => $desc) {
				$value = $this->request->getTrim($field);
				if ($value) {
					$where[$field] = $value;
				}
			}
		}
		return $where;
	}

}
