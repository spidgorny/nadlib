<?php

use nadlib\Controller\Filter;

/**
 * extends AppController is no longer working when controller is not in the same namespace
 * @property Collection $collection
 **/
trait Grid
{

	/**
	 * @var OODBase|null
	 */
	public $model;

	/**
	 * @var ?Filter
	 */
	public $filter;

	/**
	 * Defines which columns are visible in a table
	 * @var ?VisibleColumns
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
	 * @var UserModelInterface
	 */
	public $user;

//	/**
//	 * @var Collection
//	 */
//	protected Collection $collection;

	public function constructGrid()
	{
		$this->initFilter();
		$this->initPageSize();
	}

	public function initFilter(): void
	{
		$cn = $this->request->getControllerString();
		$allowEdit = $cn === get_class($this);

		llog(__METHOD__, $cn, 'allowEdit', $allowEdit);
		if ($allowEdit) {
			$this->setFilter();
		}
	}

	/**
	 * Only get filter if it's not need to be cleared
	 */
	public function setFilter(): void
	{
		if ($this->filter) {
			// already set
			return;
		}
		$this->filter = new Filter();
//		$action = $this->request->getTrim('action');
//		$this->log(__METHOD__, 'isSubmit', $this->request->isSubmit());
//		$this->log(__METHOD__, 'GET filter=', $this->request->getArray('filter'));
		if ($this->request->isSubmit() || $this->request->getArray('filter')) {
			$this->filter->setRequest($this->request->getArray('filter'));
		}

		$cn = get_class($this);
		$prefFilter = $this->user->getPref('Filter.' . $cn);
//				debug($prefFilter);
		if ($prefFilter) {
//				$this->log(__METHOD__, 'setPreferences', $prefFilter);
			$this->filter->setPreferences($prefFilter);
		}

//			d($cn, $this->filter,
//				array_keys($_SESSION), gettypes($_SESSION),
//				$_SESSION
//			);
		//debug(get_class($this), 'Filter.'.$cn, $this->filter);
//		0 && debug([
//			'controller' => $this->request->getControllerString(),
//			'this' => get_class($this),
//			//'allowEdit' => $allowEdit,
//			'this->filter' => $this->filter,
//			'_REQUEST' => $_REQUEST,
//		]);
	}

	public function initPageSize(): void
	{
		$sizeFromPreferences = $this->user->getSetting(get_class($this) . '.pageSize');
//		$this->log(__METHOD__, 'sizeFromPreferences', $sizeFromPreferences);
		$this->pageSize = new PageSize($sizeFromPreferences);
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
	 * @throws LoginException
	 */
	public function saveFilterAndSort(): void
	{
		$cn = get_class($this);

//		$this->log(__METHOD__, $cn);
		// why do we inject collection
		// before we have detected the filter (=where)?
//		if (!$this->collection) {
		//$this->injectCollection();
//		}

		//debug($cn);
		assert($cn > '');

		if ($this->filter->getArrayCopy()) {
//			$this->log(__METHOD__, 'setPref', 'Filter.' . $cn, $this->filter->getArrayCopy());
			$this->user->setPref('Filter.' . $cn, $this->filter->getArrayCopy());
		}

		//debug(spl_object_hash(Index::getInstance()->controller), spl_object_hash($this));
		//if (Index::getInstance()->controller == $this) {	// Menu may make instance of multiple controllers

		if ($this->request->is_set('slTable')) {
			$this->user->setPref('Sort.' . $cn, $this->request->getArray('slTable'));
		}

		// SORTING
		$sortRequest = $this->request->getArray('slTable');
		if ($sortRequest) {
			$this->sort = $sortRequest;
		} else {
			$this->sort = ($this->user->getPref('Sort.' . $cn) ?: $this->sort);
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
			$f = new HTMLFormTable((array)$this->filter);
			$f->method(HTMLForm::METHOD_GET);
			$f->defaultBR = true;
			$this->filter = new Filter($f->fill($this->request->getAll()));
			$f->showForm();
			$f->stdout .= $f->submit('Filter', ['class' => 'btn btn-primary']);
			$content[] = $f->getContent();
		}

		return $content;
	}

	public function getFilterWhere(): array
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
		$this->log(__METHOD__, $cn);
		// request
		$urlColumns = $this->request->getArray('columns');
		if ($allowEdit && ($urlColumns || $this->request->get('btnSubmit') === 'Set Visible Columns')) {
			llog('urlColumns', $urlColumns);
			$this->columns = new VisibleColumns($urlColumns);
			$this->user->setPref('Columns.' . $cn, $this->columns->getData());
			llog('Columns set from URL', $this->columns->getData());
			return;
		}

		if (!$this->columns) {
			$prefs = $this->user->getPref('Columns.' . $cn);
			llog('columns from getPref', $prefs);
			if ($prefs) {
				$this->columns = new VisibleColumns($prefs);
				llog(__METHOD__, 'Columns set from getPref');
				return;
			}
		}

		if ($this->model && $this->model->thes) {
			$this->columns = new VisibleColumns(array_fill_keys(array_keys($this->model->thes), true));
			llog(__METHOD__, 'Columns set from model');
			return;
		}

		if ($this->collection->thes) {
			$keysOfThes = array_keys($this->collection->thes);
			$this->columns = new VisibleColumns(array_fill_keys($keysOfThes, true));
			llog(__METHOD__, 'Columns set from collection ' . typ($this->collection) . ': ' . json_encode($this->columns, JSON_THROW_ON_ERROR));
			return;
		}

		$this->columns = new VisibleColumns();
		llog(__METHOD__, $this->columns->getData());
	}

	/**
	 * Pluck $this->thes[*]['name']
	 * To display in a column selector
	 * @return array
	 */
	public function getGridColumns()
	{
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

}
