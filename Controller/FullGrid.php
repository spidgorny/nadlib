<?php

/**
 * Class FullGrid
 * handles sorting by columns, paging, filtering,
 * selecting visible columns
 */
trait FullGrid
{
	use Grid;

	/**
	 * @var FilterController
	 */
	public $filterController;

	/**
	 * Will create collection object
	 * @throws LoginException
	 * @throws ReflectionException
	 */
	public function postInit(): void
	{
		$this->constructGrid();  // called with wrong $cn in Grid
		$this->makeCollection(); // this will create collection object
		// after construct because we need to modify join
		$this->collection->where = array_merge(
			$this->collection->where,
			$this->getFilterWhere()
		);

//			$this->log(__METHOD__, 'collection Where', $this->collection->where);

		$this->collection->pager = new Pager($this->pageSize->get());
		$this->collection->pager->setNumberOfRecords($this->collection->getCount());
		$this->collection->pager->detectCurrentPage();

		// after collection is made, to run getGridColumns
		$allowEdit = $this->request->getControllerString() === get_class($this);
//		llog('allowEdit', get_class($this), get_class($this->collection), $allowEdit);
		$this->setColumns(get_class($this), $allowEdit);
	}

	/**
	 * @throws JsonException
	 * @throws ReflectionException
	 */
	abstract public function makeCollection(): void;

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getFilterWhere(): array
	{
		return $this->filterController->getFilterWhere();
	}
//	{
//		if (is_string($collectionName)) {
//			$this->log(__METHOD__ . ' new collection', $collectionName);
//			$this->collection = new $collectionName(null, [], '', $this->db);
//			// this needs to be set after collection is created
//			$this->collection->orderBy = $this->getOrderBy();
//			return $this->collection;
//		}
//
//		$re = new ReflectionClass($this);
//		$reCol = $re->getProperty('collection');
//		$doc = new DocCommentParser($reCol->getDocComment());
//		$collectionName = $doc->getFirstTagValue('var');
//		$collectionName = first(trimExplode('|', $collectionName));
//		$this->log(__METHOD__ . ' new collection by reflection', $collectionName);
//		$this->collection = new $collectionName(null, [], '', $this->db);
//		return $this->collection;
//	}

	public function initFilter(): void
	{
		// menu is making an instance of each class because of tryMenuSuffix
		//debug(get_class($this->index->controller), get_class($this), $this->request->getControllerString());
//		parent::initFilter();
		$this->setFilter();

		// should only be called by the controller explicitly
//		$this->saveFilterAndSort();

//		if (!($this->filter instanceof nadlib\Controller\Filter)) {
//			$filterFieldsFromUrl = $this->request->getArray('filter');
//			$this->filter = new nadlib\Controller\Filter($filterFieldsFromUrl);
//		}

		$this->filterController = new FilterController();
		$this->filterController->setFilter($this->filter);
	}

	/**
	 * Can't use $this->collection at this point as this function is used to initialize the collection!
	 * @return string|null
	 * @throws JsonException
	 */
	public function getOrderBy()
	{
		$ret = '';
		$sortBy = ifsetor($this->sort['sortBy']);
		if ($this->model &&
			$this->model->thes &&
			is_array($this->model->thes[$sortBy]) &&
			ifsetor($this->model->thes[$sortBy]['source'])) {
			$sortBy = $this->model->thes[$sortBy]['source'];
		}

		$sortBy = $this->validateSortBy($sortBy);

		//			$sortBy = new SQLOrder($this->collection->orderBy);
		//			$sortBy = $sortBy->getField();
//		if (!$sortBy && !$sortBy) {
// don't do default, because a Collection has it's own default
		//$sortBy = ifsetor($this->model->idField);
//		}

		$this->log('sortBy', $sortBy);
		if ($sortBy) {
			$thes = (array)ifsetor($this->collection->thes);
			$desc = $thes[$sortBy] ?? null;
			if (is_array($desc) && ($desc['sqlSortBy'] ?? null)) {
				$sortBy = $desc['sqlSortBy'];
			}
//			$this->collection->select .= ', ' . $this->db->quoteKey($sortBy);
			$ret = 'ORDER BY ' . $this->db->quoteKey($sortBy) . ' ' .
				(ifsetor($this->sort['sortOrder']) ? 'DESC' : 'ASC');
		}

		//debug($this->sort, $sortBy);
		return $ret;
	}

	protected function validateSortBy($sortBy)
	{
		if (!$sortBy || !$this->collection->thes) {
			return null;
		}

		$thes = (array)$this->collection->thes;
		$desc = $thes[$sortBy] ?? null;
		if ($desc !== null) {
			if (is_array($desc)) {
				if (($desc['sortable'] ?? null) === false) {
					return null;
				}
				if (!empty($desc['source'])) {
					return $desc['source'];
				}
			}
			return $sortBy;
		}

		// Session/URL may contain a stale sort key. Allow only known "source"
		// aliases of sortable columns, otherwise ignore the value safely.
		foreach ($thes as $columnDesc) {
			if (!is_array($columnDesc)) {
				continue;
			}
			if (($columnDesc['sortable'] ?? null) === false) {
				continue;
			}
			if (($columnDesc['source'] ?? null) === $sortBy) {
				return $sortBy;
			}
		}

		return null;
	}

	public function sidebar()
	{
		$fields = $this->collection->thes;
		$content[] = $this->getFilterForm($fields);
		$content[] = $this->getColumnsForm();
		return $content;
	}

	/**
	 * @return HTMLForm
	 * @throws Exception
	 */
	public function getFilterForm(array $fields = [])
	{
		$this->filterController->desc = $this->getFilterDesc($fields);

		$this->filterController->linker->linkVars['c'] = get_class($this);
		return $this->filterController->render();
	}

	/**
	 * Make sure you fill the 'value' fields with data from $this->filter manually.
	 * Why manually? I don't know, it could change.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getFilterDesc(?array $fields = null)
	{
		return $this->filterController->getFilterDesc($fields);
	}

	public function render()
	{
		$this->setVisibleColumns();
		//$this->collection->pageSize = $this->pageSize;
		return parent::render();
	}

	public function setVisibleColumns(): void
	{
		if (!$this->columns) {
			throw new RuntimeException(
				'You need to set columns before setting visible columns!'
			);
		}
		llog('countVisible', $this->columns->countVisible());
		if (!$this->columns->countVisible()) {
			return;
		}
		foreach ($this->collection->thes as $cn => $_) {
			if (!$this->columns->isVisible($cn)) {
				//unset($this->collection->thes[$cn]);
				$this->collection->thes[$cn]['!show'] = true;
			}
		}
	}

	public function getColumnsForm()
	{
//		debug($this->getGridColumns());
//		debug($this->columns->getData());
		$desc = [
			'columns' => [
				'label' => '<h2>' . __('Visible') . '</h2>',
				'type' => 'keyset',
				'options' => $this->getGridColumns(),
				'value' => $this->columns->getData(),
				'between' => '',
			],
			'collectionName' => [
				'type' => 'hidden',
				'value' => get_class($this->collection),
			]
		];
		$f = new HTMLFormTable($desc);
		$f->method(HTMLForm::METHOD_GET);
		$f->defaultBR = true;
		$f->stdout .= $f->formHideArray($this->linker->linkVars);
		$f->showForm();
		$f->stdout .= $f->submit(__('Set Visible Columns'));
		return $f;
	}

	/**
	 * @throws Exception
	 */
	public function injectCollection(): void
	{
//		parent::injectCollection();
//		debug($this->collection->where,
//			$this->getFilterWhere());
		$this->collection->where = array_merge(
			$this->collection->where,
			$this->getFilterWhere()
		);
	}

}
