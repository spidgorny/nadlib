<?php

use nadlib\Controller\Filter;

abstract class Grid extends AppController
{

	/**
	 * @var OODBase|null
	 */
	public $model;

	/**
	 * @var Filter
	 */
	public $filter;

	/**
	 * Defines which columns are visible in a table
	 * @var VisibleColumns
	 */
	public $columns;

	/**
	 * @var array ['sortBy'], ['sortOrder']
	 */
	public $sort = [];

	/**
	 * @var PageSize
	 */
	public $pageSize;

	/**
	 * @var Collection
	 */
	protected $collection;

	public function __construct()
	{
		parent::__construct();
		$this->initFilter();
		$this->initPageSize();
	}

	public function initFilter(): void
	{
		$cn = $this->request->getControllerString();
		$allowEdit = $cn === get_class($this);

		if ($allowEdit) {
			$this->setFilter($cn);
		}
	}

	/**
     * Only get filter if it's not need to be cleared
     * @throws LoginException
     */
    public function setFilter(string $cn = __CLASS__): void
	{
		$this->filter = new Filter();
//		$action = $this->request->getTrim('action');
//		$this->log(__METHOD__, 'isSubmit', $this->request->isSubmit());
//		$this->log(__METHOD__, 'GET filter=', $this->request->getArray('filter'));
		if ($this->request->isSubmit() || $this->request->getArray('filter')) {
			$this->filter->setRequest($this->request->getArray('filter'));
		}

		if (method_exists($this->user, 'getPref')) {
			$prefFilter = $this->user->getPref('Filter.' . $cn);
//				debug($prefFilter);
			if ($prefFilter) {
//				$this->log(__METHOD__, 'setPreferences', $prefFilter);
				$this->filter->setPreferences($prefFilter);
			}
		}

//			d($cn, $this->filter,
//				array_keys($_SESSION), gettypes($_SESSION),
//				$_SESSION
//			);
		//debug(get_class($this), 'Filter.'.$cn, $this->filter);
		0 && debug([
			'controller' => $this->request->getControllerString(),
			'this' => get_class($this),
			//'allowEdit' => $allowEdit,
			'this->filter' => $this->filter,
			'_REQUEST' => $_REQUEST,
		]);
	}

	public function initPageSize(): void
	{
		$sizeFromPreferences = $this->user->getSetting(get_class($this) . '.pageSize');
//		$this->log(__METHOD__, 'sizeFromPreferences', $sizeFromPreferences);
		$this->pageSize = $this->pageSize
			?: new PageSize($sizeFromPreferences);
		$this->user->setSetting(get_class($this) . '.pageSize', $this->pageSize->get());
	}

	/**
	 * Either take from URL or take from preferences, not both
	 */
	public function getSetRequest(): void
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
	 * @param string $subname
	 * @throws LoginException
	 * @deprecated - use saveFilterColumnsSort() instead
	 */
	public function mergeRequest($subname = null): void
	{
		//echo '<div class="error">'.__METHOD__.get_class($this).'</div>';
        $r = $subname ? $this->request->getSubRequest($subname) : $this->request;

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
	 * @param string $cn Supply get_class($this->collection) to the function
	 * or it should be called after $this->collection is initialized
	 * @throws LoginException
	 */
	public function saveFilterAndSort($cn = null): void
	{
		if (!$cn) {
			$cn = get_class($this);
		}

//		$this->log(__METHOD__, $cn);
		// why do we inject collection
		// before we have detected the filter (=where)?
		if (!$this->collection) {
			//$this->injectCollection();
		}

		$cn = $cn ?: get_class($this->collection);
		//debug($cn);
		assert($cn > '');

		if ($this->filter && method_exists($this->user, 'setPref')) {
			//				$this->log(__METHOD__, 'setPref', $this->filter->getArrayCopy());
            $this->user->setPref('Filter.' . $cn, $this->filter->getArrayCopy());
		}

		//debug(spl_object_hash(Index::getInstance()->controller), spl_object_hash($this));
		//if (Index::getInstance()->controller == $this) {	// Menu may make instance of multiple controllers

		if (method_exists($this->user, 'setPref') && $this->request->is_set('slTable')) {
			$this->user->setPref('Sort.' . $cn, $this->request->getArray('slTable'));
		}

		// SORTING
		$sortRequest = $this->request->getArray('slTable');
		if (method_exists($this->user, 'getPref')) {
			$this->sort = $sortRequest
				?: ($this->user->getPref('Sort.' . $cn) ?: $this->sort
				);
		}
	}

	public function render()
	{
		if (!$this->collection) {
			$this->injectCollection();
		}

		$content[] = $this->collection->render();
		$content[] = '<hr />';
		return $this->encloseInAA(
			$content,
			$this->title = $this->title ?: get_class($this),
			$this->encloseTag
		);
	}

	public function injectCollection(): void
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

	public function sidebar()
	{
		return $this->showFilter();
	}

	public function showFilter()
	{
		$content = [];
		if ($this->filter) {
			$f = new HTMLFormTable($this->filter->getArrayCopy());
			$f->method('GET');
			$f->defaultBR = true;
			$this->filter = $f->fill($this->request->getAll());
			$f->showForm();
			$f->submit('Filter', ['class' => 'btn btn-primary']);
			$content[] = $f->getContent();
		}

		return $content;
	}

	public function getFilterWhere()
	{
		$where = [];
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
     * @param bool $allowEdit
     * @throws LoginException
     */
    public function setColumns(string $cn, $allowEdit): void
	{
//		$this->log(__METHOD__, $cn);
		// request
		$urlColumns = $this->request->getArray('columns');
		if ($allowEdit && $urlColumns) {
			llog('urlColumns', $urlColumns);
			$this->columns = new VisibleColumns($urlColumns);
			$this->user->setPref('Columns.' . $cn, $this->columns->getData());
			llog('Columns set from URL', $this->columns->getData());
		} elseif (!$this->columns && method_exists($this->user, 'getPref')) {
			$prefs = $this->user->getPref('Columns.' . $cn);
			if ($prefs) {
				$this->columns = new VisibleColumns($prefs);
//				llog(__METHOD__, 'Columns set from getPref');
			}
		}

		if (!$this->columns) {
			// default
			$gridColumns = array_keys($this->getGridColumns());
//			llog(__METHOD__, ['getGridColumns' => $gridColumns]);
			if ($gridColumns !== []) {
				$this->columns = new VisibleColumns($gridColumns);
//				llog(__METHOD__, 'Columns set from getGridColumns');
			}
		}

		if (!$this->columns && ifsetor($this->model->thes)) {
			$this->columns = array_keys($this->model->thes);
//			llog(__METHOD__, 'Columns set from model');
		}

		if (!$this->columns && $this->collection && $this->collection->thes) {
			$keysOfThes = array_keys($this->collection->thes);
			$this->columns = new VisibleColumns($keysOfThes);
//			llog(__METHOD__, 'Columns set from collection ' . typ($this->collection) . ': ' . json_encode($this->columns));
		} elseif (!$this->columns) {
			$this->columns = new VisibleColumns();
		}

//		llog(__METHOD__, $this->columns->getData());
	}

	/**
	 * Pluck $this->thes[*]['name']
	 * @return array
	 */
	public function getGridColumns()
	{
		if ($this->collection) {
//			llog(__METHOD__, 'Collection exists');
			return ArrayPlus::create($this->collection->thes)
				->makeTable('name')
				->column('name')
				//->combineSelf() ?!? WTF
				->mapBoth(function ($key, $val) {
					return $val ?? $key;
				})
				->getData();
		}

//		llog(__METHOD__, 'No collection');
		return [];
	}

}
