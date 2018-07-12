<?php

abstract class Grid extends AppController {

	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @var OODBase
	 */
	public $model;

	/**
	 * @var \nadlib\Controller\Filter
	 */
	public $filter = [];

	/**
	 * Defines which columns are visible in a table
	 * @var VisibleColumns
	 */
	public $columns;

	/**
	 * @var array ['sortBy'], ['sortOrder']
	 */
	public $sort;

	/**
	 * @var PageSize
	 */
	public $pageSize;

	function __construct()
	{
		parent::__construct();
		$this->initFilter();
	}

	public function initFilter()
	{
		$cn = $this->request->getControllerString();
		$allowEdit = $cn === get_class($this);

		if ($allowEdit) {
			$this->setFilter($cn);
		}
	}

	/**
	 * Either take from URL or take from preferences, not both
	 */
	function getSetRequest()
	{
		if ($this->request->getAll()) {
			$this->user->setPref(get_class($this) . '.Request', $this->request);
		} else {
			$maybe = $this->user->getPref(get_class($this) . '.Request');
			if ($maybe) {
				$this->request = $maybe;
			}
		}
	}

	/**
	 * Make sure to clone $this->request before running this function if Request is shared among controllers
	 *
	 * Take from preferences and then append/overwrite from URL
	 * How does it work when some params need to be cleared?
	 *
	 * @deprecated - use saveFilterColumnsSort() instead
	 * @param null $subname
	 */
	function mergeRequest($subname = NULL)
	{
		//echo '<div class="error">'.__METHOD__.get_class($this).'</div>';
		if ($subname) {
			$r = $this->request->getSubRequest($subname);
		} else {
			$r = $this->request;
		}
		$default = $this->user->getPref(get_class($this) . '.Request');
		if ($default instanceof Request) {
			$r->append($default->getAll());
		}
		$this->user->setPref(get_class($this) . '.Request', $r);
		if ($subname) {
			$this->request->set($subname, $r->getAll());
		}
	}

	/**
	 * @param null $cn Supply get_class($this->collection) to the function
	 * or it should be called after $this->collection is initialized
	 */
	function saveFilterAndSort($cn = NULL)
	{
//		debug(__METHOD__, $cn);
		// why do we inject collection
		// before we have detected the filter (=where)?
		if (!$this->collection) {
			//$this->injectCollection();
		}
		$cn = $cn ? $cn : get_class($this->collection);
		//debug($cn);
		assert($cn > '');

		if ($this->filter) {
			if (method_exists($this->user, 'setPref')) {
				$this->user->setPref('Filter.' . $cn, $this->filter->getArrayCopy());
			}
		}

		//debug(spl_object_hash(Index::getInstance()->controller), spl_object_hash($this));
		//if (Index::getInstance()->controller == $this) {	// Menu may make instance of multiple controllers

		if (method_exists($this->user, 'setPref')) {
			if ($this->request->is_set('slTable') && $allowEdit) {
				$this->user->setPref('Sort.' . $cn, $this->request->getArray('slTable'));
			}
		}

		// SORTING
		$sortRequest = $this->request->getArray('slTable');
		if (method_exists($this->user, 'getPref')) {
			$this->sort = $sortRequest
				? $sortRequest
				: ($this->user->getPref('Sort.' . $cn)
					? $this->user->getPref('Sort.' . $cn)
					: $this->sort
				);
		}

		// PAGE SIZE
		$this->pageSize = $this->pageSize ? $this->pageSize : new PageSize();
	}

	function render()
	{
		if (!$this->collection) {
			$this->injectCollection();
		}
		$content = $this->collection->render();
		$content .= '<hr />';
		$content = $this->encloseInAA($content,
			$this->title = $this->title ?: get_class($this),
			$this->encloseTag);
		return $content;
	}

	function injectCollection()
	{
		$class = new ReflectionObject($this);
		$col = $class->getProperty('collection');
		$comment = $col->getDocComment();
		$parser = new DocCommentParser();
		$parser->parseDocComment($comment);
		$colName = $parser->getFirstTagValue('var');
		if ($colName) {
			$this->collection = new $colName();
		}
	}

	/**
	 * This is now handled by the saveFilterColumnsSort()
	 */
	/*function clearFilterAction() {
		if ($this->request->getControllerString() == get_class($this)) {
			$this->filter = array();
			$cn = get_class($this->collection);
			$this->user->setPref('Filter.'.$cn, $this->filter);
			Index::getInstance()->message('Filter cleared');
		}
	}*/

	function sidebar()
	{
		$content = $this->showFilter();
		return $content;
	}

	function showFilter()
	{
		$content = array();
		if ($this->filter) {
			$f = new HTMLFormTable($this->filter);
			$f->method('GET');
			$f->defaultBR = true;
			$this->filter = $f->fill($this->request->getAll());
			$f->showForm($this->filter);
			$f->submit('Filter', array('class' => 'btn btn-primary'));
			$content[] = $f->getContent();
		}
		return $content;
	}

	function getFilterWhere()
	{
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
	 * Only get filter if it's not need to be cleared
	 * @param $cn
	 * @throws LoginException
	 */
	public function setFilter($cn)
	{
		$this->filter = new \nadlib\Controller\Filter();
		if ($this->request->getTrim('action') == 'clearFilter') {
			$this->filter->clear();
		} else {
			$this->filter->setRequest($this->request->getArray('filter'));
			if (method_exists($this->user, 'getPref')) {
				$prefFilter = $this->user->getPref('Filter.' . $cn);
//				debug($prefFilter);
				if ($prefFilter) {
					$this->filter->setPreferences($prefFilter);
				}
			}
//			d($cn, $this->filter,
//				array_keys($_SESSION), gettypes($_SESSION),
//				$_SESSION
//			);
			//debug(get_class($this), 'Filter.'.$cn, $this->filter);
		}
		0 && debug([
			'controller' => $this->request->getControllerString(),
			'this' => get_class($this),
			'allowEdit' => $allowEdit,
			'this->filter' => $this->filter,
			'_REQUEST' => $_REQUEST,
		]);
	}

	/**
	 * @param $cn
	 * @param $allowEdit
	 * @throws LoginException
	 */
	public function setColumns($cn, $allowEdit)
	{
		$this->log(__METHOD__, $cn);
		// request
		if ($this->request->is_set('columns') && $allowEdit) {
			$urlColumns = $this->request->getArray('columns');
			$this->columns = new VisibleColumns($urlColumns);
			$this->user->setPref('Columns.' . $cn, $this->columns->getData());
			$this->log(__METHOD__, 'Columns set from URL');
		} elseif (!$this->columns && method_exists($this->user, 'getPref')) {
			$prefs = $this->user->getPref('Columns.' . $cn);
			if ($prefs) {
				$this->columns = new VisibleColumns($prefs);
				$this->log(__METHOD__, 'Columns set from getPref');
			}
		}

		if (!$this->columns) {
			// default
			$gridColumns = array_keys($this->getGridColumns());
			$this->log(__METHOD__, ['getGridColumns' => $gridColumns]);
			if ($gridColumns) {
				$this->columns = new VisibleColumns($gridColumns);
				$this->log(__METHOD__, 'Columns set from getGridColumns');
			}
		}

		if (!$this->columns && ifsetor($this->model->thes)) {
			$this->columns = array_keys($this->model->thes);
			$this->log(__METHOD__, 'Columns set from model');
		}

		if (!$this->columns && $this->collection && $this->collection->thes) {
			$keysOfThes = array_keys($this->collection->thes);
			$this->columns = new VisibleColumns($keysOfThes);
			$this->log(__METHOD__, 'Columns set from collection ' . typ($this->collection) . ': ' . json_encode($this->columns));
		} elseif (!$this->columns) {
			$this->columns = new VisibleColumns();
		}

		$this->log(__METHOD__, $this->columns->getData());
	}

	/**
	 * Pluck $this->thes[*]['name']
	 * @return array
	 */
	function getGridColumns()
	{
		if ($this->collection) {
			$this->log(__METHOD__, 'Collection exists');
			return ArrayPlus::create($this->collection->thes)
				->makeTable('name')
				->column('name')
				//->combineSelf() ?!? WTF
				->getData();
		} else {
			$this->log(__METHOD__, 'No collection');
			return [];
		}
	}

}
