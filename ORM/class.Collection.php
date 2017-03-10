<?php

/**
 * Base class for storing datasets or datarows or tabular data or set
 * or array of OODBase based objects.
 *
 */
 /*abstract*/ // commented because of createForTable()
class Collection {
	/**
	 *
	 * @var dbLayer|MySQL|BijouDBConnector|dbLayerMS|dbLayerPDO
	 */
	public $db;

	/**
	 * @var string
	 */
	public $table = __CLASS__;
	var $idField = 'uid';
	var $parentID = NULL;
	protected $parentField = 'pid';

	/**
	 * Retrieved rows from DB
	 * @var ArrayPlus|array
	 */
	protected $data = array();

	public $thes = array();

	var $titleColumn = 'title';

	/**
	 * Basic where SQL params to be included in every SQL by default
	 * @var $this|array
	 */
	public $where = array();

	/**
	 * for LEFT OUTER JOIN queries
	 * @var string
	 */
	public $join = '';

	/**
	 * Initialize in postInit() to run paged SQL
	 * initialize if necessary with = new Pager(); in postInit()
	 * @var Pager
	 */
	public $pager;

	/**
	 * @var PageSize
	 */
	public $pageSize;

	/**
	 * objectify() stores objects generated from $this->data here
	 * array of objects converted from $this->data // convert to public
	 * @var array
	 */
	public $members = array();

	/**
	 * objectify() without parameters will try this class name
	 * @var string
	 */
	public $itemClassName = 'OODBase?';

	/**
	 * SQL part
	 * @var string
	 */
	public $orderBy = "ORDER BY id";

	/**
	 * getQuery() stores the final query here for debug
	 * @var string
	 */
	public $query;

	/**
	 * @var integer Total amount of data retrieved (not limited by Pager)
	 */
	public $count = 0;

	/**
	 * Should it be here? Belongs to the controller?
	 * @var Request
	 */
	protected $request;

	/**
	 * Indication to slTable
	 * @var bool
	 */
	public $useSorting = true;

	/**
	 * Lists columns for the SQL query
	 * @var string
	 * @default "DISTINCT table.*"
	 */
	public $select;

	public $tableMore = array(
		'class' => "nospacing",
		'width' => "100%",
	);

	public $prevText = '&#x25C4;';
	public $nextText = '&#x25BA;';

	/**
	 * @var Controller
	 */
	protected $controller;

	public $doCache = true;

	/**
	 * @var array
	 */
	public $log = array();

	/**
	 * HTMLFormTable
	 * @var array
	 */
	var $desc = array();

	/**
	 * @param integer/-1 $pid
	 * 		if -1 - will not retrieve data from DB
	 * 		if 00 - will retrieve all data
	 * 		if >0 - will retrieve data where PID = $pid
	 * @param array|SQLWhere $where
	 * @param string $order	- appended to the SQL
	 */
	function __construct($pid = NULL, /*array/SQLWhere*/ $where = array(), $order = '') {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$this->db = Config::getInstance()->db;
		$this->table = Config::getInstance()->prefixTable($this->table);
		$this->select = $this->select ? $this->select : 'DISTINCT '.$this->table.'.*';
		$this->parentID = $pid;

		if (is_array($where)) {
            // array_merge should be use instead of array union,
            // in order to prevent existing entries with numeric keys being ignored in $where
			$this->where = array_merge($this->where, $where);
		} else if ($where instanceof SQLWhere) {
			$this->where = $where->addArray($this->where);
		}

		//debug($this->where);
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

		//debug($this->parentField, $this->parentID, $this->where);

		// never retrieve data in advance
		// use lazy retrieval
		// don't access $this->data - use $this->getData()
		// don't access $this->members - use $this->objectify()
		/*if (($this->parentField && $this->parentID > 0) || (!$this->parentID && $this->where)) {
			$this->retrieveDataFromDB();
		}
		*/
		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : array('name' => $val);
		}
		$this->translateThes();
		//$GLOBALS['HTMLFOOTER']['jquery.infinitescroll.min.js'] = '<script src="js/jquery.infinitescroll.min.js"></script>';
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	function postInit() {
		//$this->pager = new Pager();
		if (class_exists('Index')) {
			$index = Index::getInstance();
			$this->controller = &$index->controller;
		}
		//debug(get_class($this->controller));
	}

	/**
	 * -1 will prevent data retrieval
	 * @param bool $allowMerge
	 * @param bool $preprocess
	 */
	function retrieveDataFromDB($allowMerge = false, $preprocess = true) {
		if ($this->db instanceof MySQL || ($this->db instanceof dbLayerPDO && $this->db->getScheme() == 'mysql')) {
			$this->log('retrieveDataFromMySQL');
			$this->retrieveDataFromMySQL($allowMerge, $preprocess);
			return;
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." (".$this->table.':'.$this->parentID.")");
		$this->query = $this->getQueryWithLimit($this->where);
		$res = $this->db->perform($this->query);
		if ($this->pager) {
			$this->count = $this->pager->numberOfRecords;
		} else {
			$this->count = $this->db->numRows($res);
		}

		$data = $this->db->fetchAll($res);
		$this->data = ArrayPlus::create($data)->IDalize($this->idField, $allowMerge);//->getData();
		if ($preprocess) {
			$this->preprocessData();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." (".$this->table.':'.$this->parentID.")");
	}

	/**
	 * https://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows
	 * @param bool $allowMerge
	 * @param bool $preprocess
	 */
	function retrieveDataFromMySQL($allowMerge = false, $preprocess = true) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." (".$this->table.':'.$this->parentID.")");
		$query = $this->getQuery();
		$sql = new SQLQuery($query);
		array_unshift($sql->parsed['SELECT'], array(
			'expr_type' => 'reserved',
			'base_expr' => 'SQL_CALC_FOUND_ROWS',
			'delim' => ' ',
		));
		//debug($sql->parsed);
		$this->query = $sql->__toString();
		$res = $this->db->perform($this->query);

		if ($this->pager) {
			$this->pager->setNumberOfRecords(PHP_INT_MAX);
			$this->pager->detectCurrentPage();
			//$this->pager->debug();
		}
		$start = $this->pager ? $this->pager->getStart() : 0;
		$limit = $this->pager ? $this->pager->getLimit() : PHP_INT_MAX;

		//debug($sql.'', $start, $limit);
		$data = $this->db->fetchPartition($res, $start, $limit);

		$countRow = $this->db->fetchAssoc($this->db->perform('SELECT FOUND_ROWS() AS count'));
		$this->count = $countRow['count'];

		if ($this->pager) {
			$this->pager->setNumberOfRecords($this->count);
			$this->pager->detectCurrentPage();
			//$this->pager->debug();
		}

		$this->data = ArrayPlus::create($data)->IDalize($this->idField, $allowMerge);//->getData();
		if ($preprocess) {
			$this->preprocessData();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." (".$this->table.':'.$this->parentID.")");
	}

	/**
	 * Wrapper for retrieveDataFromDB() to store/retrieve data from the cache file
	 * @param bool $allowMerge
	 * @param bool $preprocess
	 */
	function retrieveDataFromCache($allowMerge = false, $preprocess = true) {
		if (!$this->data) {													// memory cache
			$this->query = $this->getQuery();
			if ($this->doCache) {
				// this query is intentionally without
				if ($this->pager) {
					$this->pager->setNumberOfRecords(PHP_INT_MAX);
					$this->pager->detectCurrentPage();
					//$this->pager->debug();
				}
				$fc = new MemcacheOne($this->query.'.'.$this->pager->currentPage, 60*60);			// 1h
				$this->log('key: '.substr(basename($fc->map()), 0, 7));
				$cached = $fc->getValue();									// with limit as usual
				if ($cached && sizeof($cached) == 2) {
					list($this->count, $this->data) = $cached;
					if ($this->pager) {
						$this->pager->setNumberOfRecords($this->count);
						$this->pager->detectCurrentPage();
					}
					$this->log('found in cache, age: '.$fc->getAge());
				} else{
					$this->retrieveDataFromDB($allowMerge, $preprocess);	// getQueryWithLimit() inside
					$fc->set(array($this->count, $this->data));
					$this->log('no cache, retrieve, store');
				}
			} else {
				$this->retrieveDataFromDB($allowMerge, $preprocess);
			}
			if ($_REQUEST['d']) {
				//debug($cacheFile = $fc->map($this->query), $action, $this->count, filesize($cacheFile));
			}
		}
	}

	function log($msg) {
		$this->log[(string)microtime(true)] = $msg;
	}

	/**
	 * @param array/SQLWhere $where
	 * @return string
	 */
	function getQuery($where = array()) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if (!$where) {
			$where = $this->where;
		}
		if ($this->parentID > 0) {
			$where[$this->parentField] = $this->parentID;
		}
		// bijou old style - each collection should care about hidden and deleted
		//$where += $GLOBALS['db']->filterFields($this->filterDeleted, $this->filterHidden, $GLOBALS['db']->getFirstWord($this->table));
		if ($where instanceof SQLWhere) {
			$query = $this->db->getSelectQuerySW($this->table.' '.$this->join, $where, $this->orderBy, $this->select, TRUE);
		} else {
			$query = $this->db->getSelectQuery(
				$this->table.' '.$this->join,
				$where,
				$this->orderBy,
				$this->select,
				TRUE);
		}
		//debug($query);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $query;
	}

	function getQueryWithLimit() {
		$query = $this->getQuery();
		if ($this->pager) {
			//debug($this->pager->getObjectInfo());
			$this->pager->initByQuery($query);
			$query .= $this->pager->getSQLLimit();
		}
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

	/**
	 * @return slTable|string - returns the slTable if not using Pager
	 */
	function render() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		if ($this->data) {
			$this->prepareRender();
			//debug($this->tableMore);
			$s = $this->getDataTable();
			if ($this->pager) {
				$url = new URL();
				$pages = $this->pager->renderPageSelectors($url);
				$content = $pages . $s->getContent(get_class($this)) . $pages;
			} else {
				$content = $s;
			}
		} else {
			$content = '<div class="message">No data</div>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
		return $content;
	}

	function getDataTable() {
		$s = new slTable($this->data, HTMLTag::renderAttr($this->tableMore));
		$s->thes($this->thes);
		$s->ID = get_class($this);
		$s->sortable = $this->useSorting;
		if (class_exists('Index')) {
			$s->setSortBy(Index::getInstance()->controller->sortBy);	// UGLY
			//debug(Index::getInstance()->controller);
			$s->sortLinkPrefix = new URL(NULL, Index::getInstance()->controller->linkVars ? Index::getInstance()->controller->linkVars : array());
		}
		return $s;
	}

	function prepareRender() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$this->table})");
		$this->getData();
		foreach ($this->data as &$row) { // Iterator by reference
			$row = $this->prepareRenderRow($row);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$this->table})");
	}

	/**
	 * @return array|ArrayPlus
	 */
	function getData() {
		if (!$this->query || (
				!$this->data
				|| !$this->data->count())) {
			$this->retrieveDataFromDB();
		}
		if (!($this->data instanceof ArrayPlus)) {
			$this->data = ArrayPlus::create($this->data);
			$this->count = sizeof($this->data);
		}
		return $this->data;
	}

	function prepareRenderRow(array $row) {
		return $row;
	}

    /**
     * @param array $blackList Contains IDs that should be filtered out from options
     * @return array
     */
    function getOptions($blackList = array()) {
		$options = array();
		//debug(get_class($this), $this->titleColumn);
		foreach ($this->getData() as $row) {
            if( !in_array($row[$this->idField], $blackList) ) {
                $options[$row[$this->idField]] = $row[$this->titleColumn];
            }
		}
		return $options;
	}

	/**
	 * @param array $where
	 * @return mixed - single row
	 */
	function findInData(array $where) {
		//debug($where);
		//echo new slTable($this->data);
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				return $row;
			}
		}
	}

	/**
	 * @param array $where
	 * @return array - of matching rows
	 */
	function findAllInData(array $where) {
		$result = array();
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 == $intersect2) {
				$result[] = $row;
			}
		}
		return $result;
	}

	function renderList() {
		$content = '<ul>';
		foreach ($this->getData() as $row) {
			$content .= '<li>';
			foreach ($this->thes as $key => $_) {
				$content .= $row[$key]. ' ';
			}
			$content .= '</li>';
		}
		$content .= '</ul>';
		return $content;
	}

	/**
	 * Calls __toString on each member
	 * @return string
	 */
	function renderMembers() {
		$content = '';
		//debug(sizeof($this->members));
		if ($this->objectify()) {
			foreach ($this->objectify() as $key => $obj) {
				//debug($i++, (strlen($content)/1024/1024).'M');
				if (is_object($obj)) {
					$content .= $obj->render()."\n";
				} else {
					$content .= getDebug(__METHOD__, $key, $obj);
				}
			}
		} else {
			$content .= '<div class="message">'.__('No data').'</div>';
		}
		if ($this->pager) {
			//$this->pager->debug();
			$url = new URL();
			$pages = $this->pager->renderPageSelectors($url);
			$content = $pages . $content . $pages;
		}
		return $content;
	}

	function translateThes() {
		if (is_array($this->thes)) foreach ($this->thes as &$trans) {
			if (is_string($trans) && $trans) {
				$trans = __($trans);
			}
		}
		$this->prevText = __($this->prevText);
		$this->nextText = __($this->nextText);
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @param string $orderBy
	 * @return Collection
	 */
	static function createForTable($table, array $where = array(), $orderBy = '') {
		$c = new self(-1);
		$c->table = $table;
		$c->where = $where;
		$c->orderBy = $orderBy;
		$db = $GLOBALS['db'];
		$firstWord = $db->getFirstWord($c->table);
		$c->select = ' '.$firstWord.'.*';
		return $c;
	}

	/**
	 * Will detect double-call and do nothing.
	 *
	 * @param string $class	- required, but is supplied by the subclasses
	 * @param bool $byInstance
	 * @return object[]
	 */
	function objectify($class = NULL, $byInstance = false) {
		$class = $class ?: $this->itemClassName;
		if (!$this->members) {
			foreach ($this->getData() as $row) {
				$key = $row[$this->idField];
				if ($byInstance) {
					//$this->members[$key] = call_user_func_array(array($class, 'getInstance'), array($row));
					$this->members[$key] = call_user_func($class.'::getInstance', $row);
				} else {
					$this->members[$key] = new $class($row);
				}
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
			'name' => '<a href="javascript:void(0)"><input type="checkbox" id="checkAllAuto" name="checkAllAuto" onclick="checkAll()" /></a>', // if we need sorting here just add ""
            'align' => "center",
			'no_hsc' => true,
		)) + $this->thes;
		$class = get_class($this);
		foreach ($this->data as &$row) {
			$id = $row[$this->idField];
			$checked = $_SESSION[$class][$id] ? 'checked="checked"' : '';
			$row['checked'] = '<form method="POST"><input type="checkbox" name="'.$class.'['.$id.']" value="'.$id.'" '.$checked.' /></form>';
		}
	}

	function showFilter() {
		if ($this->filter) {
			$f = new HTMLFormTable();
			$f->method('GET');
			$f->defaultBR = true;
			$this->filter = $f->fillValues($this->filter, $this->request->getAll());
			$f->showForm($this->filter);
			$f->submit('Filter', array('class' => 'btn btn-primary'));
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

	/**
	 * Uses array_merge to prevent duplicates
	 * @param Collection $c2
	 */
	function mergeData(Collection $c2) {
		$before = array_keys($this->data);
		//$this->data = array_merge($this->data, $c2->data);	// don't preserve keys
		$this->data = $this->data + $c2->data;
		$this->members = $this->members + $c2->members;
		$this->count = sizeof($this->data);
		//debug($before, array_keys($c2->data), array_keys($this->data));
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
	 * Only $model->id is used to do ArrayPlus::getNextKey() and $mode->getName() for display
	 *
	 * If pager is used then it tries to retrieve page before and after to make sure that first and last
	 * elements on the page still have prev and next elements. But it's SLOW!
	 *
	 * @param OODBase $model
	 * @return string
	 */
	function getNextPrevBrowser(OODBase $model) {
		if ($this->pager) {
			//$this->pager->debug();
			if ($this->pager->currentPage > 0) {
				$copy = clone $this;
				$copy->pager->setCurrentPage($copy->pager->currentPage-1);
				$copy->retrieveDataFromCache();
				$copy->preprocessData();
				$prevData = $copy->data;
			} else {
				$prevData = array();
			}

			$pageKeys = array_keys($this->data);
			if ($this->pager->currentPage < $this->pager->getMaxPage() &&
				end($pageKeys) == $model->id	// last element on the page
			) {
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
		$data = $prevData + (
            ($this->data instanceof ArrayPlus) ? $this->data->getData() : $this->data
            ) + $nextData; // not array_merge which will reindex

		nodebug($model->id,
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($prevData))),
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys((array)$this->data))),
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($nextData)))
		);
		$ap = AP($data);
		//debug($data);

		$prev = $ap->getPrevKey($model->id);
		if ($prev) {
			$prev = $this->getNextPrevLink($data[$prev], $this->prevText);
		} else {
			$prev = '<span class="muted">'.$this->prevText.'</span>';
		}

		$next = $ap->getNextKey($model->id);
		if ($next) {
			$next = $this->getNextPrevLink($data[$next], $this->nextText);
		} else {
			$next = '<span class="muted">'.$this->nextText.'</span>';
		}

		$content = $this->renderPrevNext($prev, $model, $next);

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

	/**
	 * Override to make links from different type of objects
	 * @param $prev
	 * @param $arrow
	 * @return HTMLTag
	 */
	protected function getNextPrevLink(array $prev, $arrow) {
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

	protected function renderPrevNext($prev, $model, $next) {
		return $prev.' '.$model->getName().' '.$next;
	}

	function getObjectInfo() {
		$list = array();
		foreach ($this->members as $obj) {
			/** @var $obj OODBase */
			$list[] = $obj->getObjectInfo();
		}
		return $list;
	}

	function getLazyIterator() {
		$query = $this->getQuery();

		$lazy = new DatabaseResultIteratorAssoc($this->db, $this->idField);
		$lazy->perform($query);

		return $lazy;
	}

	function getLazyMemberIterator($class) {
		$arrayIterator = $this->getLazyIterator();
		$memberIterator = new LazyMemberIterator($arrayIterator, 0, $class);
		$memberIterator->count = $arrayIterator->count();
		return $memberIterator;
	}

	public function getCount() {
		$this->query = $this->getQuery($this->where);
		$res = $this->db->perform($this->query);
		if ($this->pager) {
			$this->count = $this->pager->numberOfRecords;
		} else {
			$this->count = $this->db->numRows($res);
		}
		return $this->count;
	}

}
