<?php

/**
 * Base class for storing datasets or datarows or tabular data or set
 * or array of OODBase based objects.
 *
 */
 /*abstract*/ // commented because of createForTable()
class Collection implements IteratorAggregate {

	/**
	 * In case of MSSQL it needs to be set from outside
	 * @var dbLayer|MySQL|BijouDBConnector|dbLayerMS|dbLayerPDO|dbLayerSQLite
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
	 * Protected in order to force usage of getData()
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
	protected $members = array();

	/**
	 * objectify() without parameters will try this class name
	 * @var string
	 */
	protected $itemClassName = 'itemClassNameNotExists';

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
	 * Is NULL until it's set to 0 or more
	 * @var integer Total amount of data retrieved (not limited by Pager)
	 */
	public $count = NULL;

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

	var $noDataMessage = 'No data';

	var $filter = array();

	/**
	 * @param integer/-1 $pid
	 * 		if -1 - will not retrieve data from DB
	 * 		if 00 - will retrieve all data
	 * 		if >0 - will retrieve data where PID = $pid
	 * @param array|SQLWhere $where
	 * @param string $order	- appended to the SQL
	 */
	function __construct($pid = NULL, /*array/SQLWhere*/ $where = array(), $order = '') {
		TaylorProfiler::start(__METHOD__." ({$this->table})");
		$this->db = Config::getInstance()->getDB();
		$this->table = Config::getInstance()->prefixTable($this->table);
		$this->select = $this->select ?: 'DISTINCT '.$this->db->getFirstWord($this->table).'.*';
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

		foreach ($this->thes as &$val) {
			$val = is_array($val) ? $val : array('name' => $val);
		}
		$this->translateThes();
		TaylorProfiler::stop(__METHOD__." ({$this->table})");
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
		$this->log(get_class($this).'::'.__FUNCTION__.'('.$allowMerge.', '.$preprocess.')');
		//debug(__METHOD__, $allowMerge, $preprocess);
		if (phpversion() > 5.3 && (
			$this->db instanceof MySQL
			|| ($this->db instanceof dbLayerPDO && $this->db->getScheme() == 'mysql')
		)) {
			$this->log('retrieveDataFromMySQL');
			$this->retrieveDataFromMySQL($allowMerge, $preprocess);
			return;
		}
		$tableParent = " (".$this->table.':'.(is_array($this->parentID)
				? implode(', ', $this->parentID)
				: $this->parentID).")";
		TaylorProfiler::start(__METHOD__." ({$this->table})");
		$this->query = $this->getQueryWithLimit();
		//debug($this->query);
		$res = $this->db->perform($this->query);
		if ($this->pager) {
			$this->count = $this->pager->numberOfRecords;
		} else {
			$this->count = $this->db->numRows($res);
		}

		$data = $this->db->fetchAll($res);
		$this->data = ArrayPlus::create($data)->IDalize($this->idField, $allowMerge);//->getData();
		if ($preprocess) {
			$this->db->free($res);
			$this->preprocessData();
		}
		TaylorProfiler::stop(__METHOD__." ({$this->table})");
	}

	/**
	 * https://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows
	 * @param bool $allowMerge
	 * @param bool $preprocess
	 * @requires PHP 5.3
	 */
	function retrieveDataFromMySQL($allowMerge = false, $preprocess = true) {
		$taylorKey = __METHOD__." (".$this->table.':'.(is_array($this->parentID)
						? json_encode($this->parentID)
						: $this->parentID).")";
		TaylorProfiler::start($taylorKey);
		$query = $this->getQuery();
		if (class_exists('PHPSQL\Parser') && false) {
			$sql = new SQLQuery($query);
			//debug($sql->parsed['SELECT']);
			array_unshift($sql->parsed['SELECT'], array(
				'expr_type' => 'reserved',
				'base_expr' => 'SQL_CALC_FOUND_ROWS',
				'delim'     => ' ',
			));
			//debug($sql->parsed);
			if ($sql->parsed['ORDER'] && $sql->parsed['ORDER'][0]['base_expr'] != 'FIELD') {
				$sql->parsed['ORDER'][0]['expr_type'] = 'colref';
			}
			//debug($sql->parsed);
			$this->query = $sql->__toString();
		} else {
			//$this->query = str_replace('SELECT ', 'SELECT SQL_CALC_FOUND_ROWS ', $query);	// subquery problem
			$this->query = preg_replace('/SELECT /', 'SELECT SQL_CALC_FOUND_ROWS ', $query, 1);
		}
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
			$this->db->free($res);
			$this->preprocessData();
		}
		TaylorProfiler::stop($taylorKey);
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
		$time = (string)microtime(true);
		if (isset($this->log[$time])) {
			$time .= '.'.uniqid();
		}
		$this->log[$time] = $msg;
	}

	/**
	 * @param array/SQLWhere $where
	 * @return string
	 */
	function getQuery($where = array()) {
		TaylorProfiler::start(__METHOD__." ({$this->table})");
		if (!$this->db) {
			debug_pre_print_backtrace();
		}
		if (!$where) {
			$where = $this->where;
		}
		if ($this->parentID > 0) {
			if ($this->parentID instanceof Date) {
				$where[$this->parentField] = $this->parentID->getMySQL();
			} elseif ($this->parentID instanceof OODBase) {
				$where[$this->parentField] = $this->parentID->id;
			} else {
				$where[$this->parentField] = is_array($this->parentID)
					? new SQLIn($this->parentID)
					: $this->parentID;
			}
		}
		// bijou old style - each collection should care about hidden and deleted
		//$where += $GLOBALS['db']->filterFields($this->filterDeleted, $this->filterHidden, $GLOBALS['db']->getFirstWord($this->table));
		if ($where instanceof SQLWhere) {
			$query = $this->db->getSelectQuerySW($this->table.' '.$this->join, $where, $this->orderBy, $this->select, TRUE);
		} else {
			//debug_pre_print_backtrace();
			$query = $this->db->getSelectQuery(
				$this->table.' '.$this->join,
				$where,
				$this->orderBy,
				$this->select,
				TRUE);
		}
		//debug($query);
		TaylorProfiler::stop(__METHOD__." ({$this->table})");
		return $query;
	}

	function getQueryWithLimit() {
		$query = $this->getQuery();
		if ($this->pager) {
			//debug($this->pager->getObjectInfo());
			$this->pager->initByQuery($query);
			$query .= $this->pager->getSQLLimit();
		}
		//debug($query);
		//TaylorProfiler::stop(__METHOD__." ({$this->table})");
		return $query;
	}

	function preprocessData() {
		TaylorProfiler::start(__METHOD__." ({$this->table})");
		$this->log(get_class($this).'::'.__FUNCTION__.'()');
		foreach ($this->data as $i => &$row) { // Iterator by reference
			$row = $this->preprocessRow($row);
		}
		TaylorProfiler::stop(__METHOD__." ({$this->table})");
	}

	function preprocessRow(array $row) {
		return $row;
	}

	/**
	 * @return slTable|string - returns the slTable if not using Pager
	 */
	function render() {
		TaylorProfiler::start(__METHOD__." ({$this->table})");
		$this->log(get_class($this).'::'.__FUNCTION__.'()');
		$this->getData();
		if ($this->count) {
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
			$content = '<div class="message alert alert-warning">'.__($this->noDataMessage).'</div>';
		}
		TaylorProfiler::stop(__METHOD__." ({$this->table})");
		return $content;
	}

	function getDataTable() {
		$this->log(get_class($this).'::'.__FUNCTION__.'()');
		$s = new slTable($this->getData(), HTMLTag::renderAttr($this->tableMore));
		$s->thes($this->thes);
		$s->ID = get_class($this);
		$s->sortable = $this->useSorting;
		if (class_exists('Index')) {
			$s->setSortBy(ifsetor(Index::getInstance()->controller->sortBy));	// UGLY
			//debug(Index::getInstance()->controller);
			$s->sortLinkPrefix = new URL(NULL,
					ifsetor(Index::getInstance()->controller->linkVars)
					? Index::getInstance()->controller->linkVars
					: array());
		}
		return $s;
	}

	function prepareRender() {
		TaylorProfiler::start(__METHOD__." ({$this->table})");
		$this->log(get_class($this).'::'.__FUNCTION__.'()');
		$this->getData();
		foreach ($this->data as &$row) { // Iterator by reference
			$row = $this->prepareRenderRow($row);
		}
		TaylorProfiler::stop(__METHOD__." ({$this->table})");
	}

	/**
	 * @return ArrayPlus
	 */
	function getData() {
		$this->log(get_class($this).'::'.__FUNCTION__.'()');
		$this->log('query: '.!!$this->query);
		$this->log('data: '.!!$this->data);
		$this->log('data->count: '.count($this->data));
		if (!$this->query
			|| is_null($this->data)
			//|| !$this->data->count())) {
		) {
			$this->retrieveDataFromDB();
		}
		if (!($this->data instanceof ArrayPlus)) {
			$this->data = ArrayPlus::create($this->data);
			$this->count = sizeof($this->data);
		}
		return $this->data;
	}

	/**
	 * A function to fake the data as if it was retrieved from DB
	 * @param $data
	 */
    function setData($data) {
	    $this->log(get_class($this).'::'.__FUNCTION__.'()');
	    if ($data instanceof ArrayPlus) {
		    $this->data = $data;    // preserve sorting
	    } else {
		    $this->data = ArrayPlus::create((array)$data);
	    }
        $this->count = count($this->data);

		// this is needed to not retrieve the data again after it was set (see $this->getData() which is called in $this->render())
		$this->query = true;
    }

	function prepareRenderRow(array $row) {
		return $row;
	}

    /**
     * @return array
     */
    function getOptions() {
		$options = array();
		//debug(get_class($this), $this->table, $this->titleColumn, $this->getCount());
		foreach ($this->getData() as $row) {
            //if ( !in_array($row[$this->idField], $blackList) ) {
                $options[$row[$this->idField]] = $row[$this->titleColumn];
            //}
		}
		return $options;
	}

    /**
     * @return array
     */
    function getLinks() {
		$options = array();
		//debug(get_class($this), $this->table, $this->titleColumn, $this->getCount());
		//debug($this->itemClassName, $this->idField, $this->titleColumn, sizeof($this->members), first($this->getData()->getData()));
		foreach ($this->objectify() as $obj) {
			//debug($obj->id, $obj->getName());
            $options[$obj->id] = $obj->getNameLink();
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
		return NULL;
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
		$list = array();
		if ($this->getCount()) {
			foreach ($this->getData() as $id => $row) {
				if ($this->thes) {
					$row = $this->prepareRenderRow($row);   // add link
					$item = '';
					foreach ($this->thes as $key => $_) {
						$item .= $row[$key] . ' ';
					}
					$list[$id] = $item;
				} elseif ($this->itemClassName) {
					/** @var OODBase $obj */
					$class = $this->itemClassName;
					if (method_exists($class, 'getInstance')) {
						$obj = $class::getInstance($row);
					} else {
						$obj = new $class($row);
					}
					if (method_exists($obj, 'getSingleLink')) {
						$list[$id] = new HTMLTag('a', array(
							'href' => $obj->getsingleLink(),
						), $obj->getName());
					} else {
						$list[$id] = $obj->getName();
					}
				} else {
					$list[$id] = $row[$this->titleColumn];
				}
			}
			return new UL($list);
		}
		return NULL;
	}

	/**
	 * Calls __toString on each member
	 * @return string
	 */
	function renderMembers() {
		$content = array();
		//debug(sizeof($this->members));
		if ($this->objectify()) {
			$content[] = '<div class="'.get_class($this).'">';
			foreach ($this->objectify() as $key => $obj) {
			//debug($i++, (strlen($content)/1024/1024).'M');
				if (is_object($obj)) {
					$content[] = $obj->render();
					$content[] = "\n";
				} else {
					$content[] = getDebug(__METHOD__, $key, $obj);
				}
			}
			$content[] = '</div>';
		} else {
			//Index::getInstance()->ll->debug = true;
			$content[] = '<div class="message alert alert-warning">'.__($this->noDataMessage).'</div>';
		}
		if ($this->pager) {
			//$this->pager->debug();
			$url = new URL();
			$pages = $this->pager->renderPageSelectors($url);
			$content = array($pages, $content, $pages);
		}
		return $content;
	}

	function translateThes() {
		if (is_array($this->thes)) foreach ($this->thes as &$trans) {
			if (is_string($trans) && $trans) {
				$trans = __($trans);
			}
		}
		//debug_pre_print_backtrace();
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
		/** @var dbLayerBase $db */
		$db = Config::getInstance()->getDB();
		$firstWord = $db->getFirstWord($c->table);
		$c->select = ' '.$firstWord.'.*';
		return $c;
	}

	/**
	 * Will detect double-call and do nothing.
	 *
	 * @param string $class	- required, but is supplied by the subclasses
	 * @param bool $byInstance
	 * @return object[]|OODBase[]
	 */
	function objectify($class = NULL, $byInstance = false) {
		$this->members = array();   // somehow necessary
		$class = $class ? $class : $this->itemClassName;
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
     * @param string $idFieldName Optional param to define a different ID field to use as checkbox value
     */
    function addCheckboxes($idFieldName = '') {
        $this->thes = array('checked' => array(
                'name' => '<a href="javascript:void(0)">
					<input type="checkbox"
					id="checkAllAuto"
					name="checkAllAuto"
					onclick="checkAll()" /></a>', // if we need sorting here just add ""
                'align' => "center",
                'no_hsc' => true,
            )) + $this->thes;
        $class = get_class($this);
        foreach ($this->data as &$row) {
            $id = !empty($idFieldName) ? $row[$idFieldName] : $row[$this->idField];
            $checked = ifsetor($_SESSION[$class][$id]) ? 'checked="checked"' : '';
            $row['checked'] = '<form method="POST"><input type="checkbox" name="'.$class.'['.$id.']" value="'.$id.'" '.$checked.' /></form>';
        }
    }

	function showFilter() {
		$content = array();
		if ($this->filter) {
			$f = new HTMLFormTable();
			$f->method('GET');
			$f->defaultBR = true;
			$this->filter = $f->fillValues($this->filter, $this->request->getAll());
			$f->showForm($this->filter);
			$f->submit('Filter', array('class' => 'btn btn-primary'));
			$content[] = $f->getContent();
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
	 * @throws LoginException
	 * @throws Exception
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

		$central = ($this->data instanceof ArrayPlus)
			? $this->data->getData()
			: ($this->data ? $this->data : array())  // NOT NULL
		;

		nodebug($model->id,
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($prevData))),
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys((array)$this->data))),
			str_replace($model->id, '*'.$model->id.'*', implode(', ', array_keys($nextData)))
		);
		$data = $prevData + $central + $nextData; // not array_merge which will reindex
		$ap = ArrayPlus::create($data);
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

	function getLazyMemberIterator($class = NULL) {
		if (!$class) {
			$class = $this->itemClassName;
		}
		$arrayIterator = $this->getLazyIterator();
		$memberIterator = new LazyMemberIterator($arrayIterator, 0, $class);
		$memberIterator->count = $arrayIterator->count();
		return $memberIterator;
	}

	/**
	 * Don't update $this->query otherwise getData() will think we have
	 * retrieved nothing.
	 * @return int
	 */
	public function getCount() {
		if (is_null($this->count)) {
			$query = $this->getQueryWithLimit();
			//debug($query);
			if ($this->pager) {
				$this->count = $this->pager->numberOfRecords;
			} else {
				$res = $this->db->perform($query);
				$this->count = $this->db->numRows($res);
			}
		}
		return $this->count;
	}

	function clearInstances() {
		unset($this->data);
		unset($this->members);
	}

	function getJson() {
		$members = array();
		foreach ($this->objectify() as $id => $member) {
			$members[$id] = $member->getJson();
		}
		return array(
			'class' => get_class($this),
			'count' => $this->getCount(),
			'members' => $members,
		);
	}

	function reload() {
		$this->query = NULL;
		$this->retrieveDataFromDB();
	}

	/**
	 * @param object $obj
	 * @return bool
	 */
	function contains($obj) {
		foreach ($this->objectify() as $mem) {
			if ($mem == $obj) {
				return true;
			}
		}
		return false;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 */
	public function getIterator() {
		return new ArrayPlus($this->objectify());
	}

	function get($id) {
		$members = $this->objectify();
		return $members[$id];
	}

}
