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
	public $query;

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
		//$GLOBALS['HTMLFOOTER']['jquery.infinitescroll.min.js'] = '<script src="js/jquery.infinitescroll.min.js"></script>';
	}

	function postInit() {
		//$this->pager = new Pager();
	}

	function __clone() {
		if ($this->pager) {
			$this->pager = clone $this->pager;
		}
	}

	function retrieveDataFromDB() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$this->query = $this->getQuery($this->where);
		$res = $this->db->perform($this->query);
		$data = $this->db->fetchAll($res);
		$this->data = ArrayPlus::create($data)->IDalize($this->idField)->getData();
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
		foreach ($this->data as &$row) { // Iterator by reference
			$row = $this->preprocessRow($row);
		}
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function preprocessRow(array $row) {
		return $row;
	}

	function render() {
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if ($this->data) {
			$url = new URL();
			$pages = $this->pager ? $this->pager->renderPageSelectors($url) : '';
			$s = new slTable(get_class($this), 'class="nospacing" width="100%"', $this->thes);
			$s->data = $this->data;
			$content = $pages . $s->getContent() . $pages;
		} else {
			$content = '<div class="message">No data</div>';
		}
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

	function renderMembers() {
		$content = '';
		foreach ($this->members as $obj) {
			$content .= $obj."\n";
		}
		return $content;
	}

	function translateThes() {
		// translate thes
		if (is_array($this->thes)) foreach ($this->thes as $key => &$trans) {
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
			'name' => '<a href="javascript:void(0);" onclick="checkAll(this)">All</a>',
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
			$request = new Request();
			$this->filter = $f->fillValues($this->filter, $request->getAll());
			$f->showForm($this->filter);
			$f->submit('Filter', '', array('class' => 'btn-primary'));
			$content = $f->getContent();
		}
		return $content;
	}

	function getFilterWhere() {
		$where = array();
		if ($this->filter) {
			$request = new Request();
			foreach ($this->filter as $field => $desc) {
				$value = $request->getTrim($field);
				if ($value) {
					$where[$field] = $value;
				}
			}
		}
		return $where;
	}

	/**
	 * Still buggy. This has to be much more simple.
	 * @param Grid $grid
	 * @return string
	 */
/*	function getNextPrevBrowser(OODBase $model) {
		$content = '';

		//if ($this->data[$model->id]) { // current page contains current element

		$ap = AP($this->data);
		//debug($this->pager->currentPage, implode(', ', array_keys($this->data)));

		$prev = $ap->getPrevKey($model->id);
		if ($prev) {
			$prev = $this->getNextPrevLink($this->data[$prev], '&#x25C4;');
		} else if ($this->pager) {
			$copy = clone $this;
			$copy->pager->setCurrentPage($copy->pager->currentPage-1);
			$copy->retrieveDataFromDB();
			$copy->preprocessData();

			$ap2 = AP($copy->data);
			$prev2 = $ap2->getPrevKey($model->id);
			$prev = $this->getNextPrevLink($copy->data[$prev2] ?: end($copy->data), '&#x25C4;');
			//debug('prev', $copy->pager->currentPage, implode(', ', array_keys($copy->data)));
		}

		$next = $ap->getNextKey($model->id);
		if ($next) {
			$next = $this->getNextPrevLink($this->data[$next], '&#x25BA;');
		} else if ($this->pager) {
			$copy = clone $this;
			$copy->pager->setCurrentPage($copy->pager->currentPage+1);
			$copy->retrieveDataFromDB();
			$copy->preprocessData();

			$ap2 = AP($copy->data);
			$next2 = $ap2->getNextKey($model->id);
			$next = $this->getNextPrevLink($copy->data[$next2] ?: first($copy->data), '&#x25BA;');
			//debug('next', $copy->pager->currentPage, implode(', ', array_keys($copy->data)));
		}

		$content = $prev.' '.$model->getName().' '.$next;
		return $content;
	}*/

	/**
	 * @param OODBase $model
	 * @return string
	 */
	function getNextPrevBrowser(OODBase $model) {
		if ($this->pager) {
			if ($this->pager->currentPage > 0) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage-1);
				$copy->retrieveDataFromDB();
				$copy->preprocessData();
				$prevData = $copy->data;
			} else {
				$prevData = array();
			}

			if ($this->pager->currentPage < $this->pager->getMaxPage()) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage+1);
				$copy->retrieveDataFromDB();
				$copy->preprocessData();
				$nextData = $copy->data;
			} else {
				$nextData = array();
			}
		} else {
			$prevData = $nextData = array();
		}
		$data = $prevData + $this->data + $nextData; // not array_merge which will reindex

		nodebug($model->id,
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($prevData))),
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($this->data))),
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($nextData)))
		);
		$ap = AP($data);

		$prev = $ap->getPrevKey($model->id);
		if ($prev) {
			$prev = $this->getNextPrevLink($data[$prev], '&#x25C4;');
		}

		$next = $ap->getNextKey($model->id);
		if ($next) {
			$next = $this->getNextPrevLink($data[$next], '&#x25BA;');
		}

		$content = $prev.' '.$model->getName().' '.$next;

		// switch page for the next time
		if (isset($prevData[$model->id])) {
			$this->pager->setCurrentPage($this->pager->currentPage-1);
			$this->pager->saveCurrentPage();
		}
		if (isset($nextData[$model->id])) {
			$this->pager->setCurrentPage($this->pager->currentPage+1);
			$this->pager->saveCurrentPage();
		}

		return $content;
	}

	protected function getNextPrevLink($prev, $arrow) {
		if ($prev['singleLink']) {
			$content = new HTMLTag('a', array(
				'href' => $prev['singleLink'],
				'title' => $prev['name'],
			),
				//'&lt;',			// <
				//'&#x21E6;',			// ⇦
				//'&#25C0;',		// ◀
				//'&#x25C4;',		// ◄
				$arrow,
				true);
		} else {
			$content = $arrow;
		}
		return $content;
	}

}
