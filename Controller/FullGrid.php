<?php

use nadlib\Controller\Filter;

abstract class FullGrid extends Grid {

	/**
	 * @var FilterController
	 */
	var $filterController;

	/**
	 */
	function __construct()
	{
		parent::__construct();	// calls $this->initFilter();
	}

	public function initFilter()
	{
		// menu is making an instance of each class because of tryMenuSuffix
		//debug(get_class($this->index->controller), get_class($this), $this->request->getControllerString());
		parent::initFilter();

		$allowEdit = $this->request->getControllerString() == get_class($this);
		if ($allowEdit /*&& $collection*/) {
			$this->saveFilterAndSort(/*$collection ?: */get_class($this));
		}

		if (!($this->filter instanceof nadlib\Controller\Filter)) {
//			debug($this->filter);
			$this->filter = new nadlib\Controller\Filter($this->filter);
//			debug(gettype2($this->filter));
		}

		$this->filterController = new FilterController();
		$this->filterController->setFilter($this->filter);
	}

	/**
	 * @param null $collection
	 * @throws LoginException
	 */
	function postInit($collection = NULL) {
		if (!$this->collection) {
			if (is_string($collection)) {
				$this->log(__METHOD__ . ' new collection', $collection);
				$this->collection = new $collection(NULL, [], $this->getOrderBy());
				// after construct because we need to modify join
				$this->collection->where = array_merge(
					$this->collection->where,
					$this->getFilterWhere()
				);

				//debug($this->collection->where);
				//file_put_contents('tests/Fixture/SoftwareGridApostrophe.serial', serialize($this->collection->where));
				//debug($this->collection->getQuery());

				$this->collection->postInit();
				$this->collection->pager = new Pager($this->pageSize ? $this->pageSize->get() : NULL);
			} else {
				if (!$collection) {
					$re = new ReflectionClass($this);
					$reCol = $re->getProperty('collection');
					$doc = new DocCommentParser($reCol->getDocComment());
					$collectionName = $doc->getFirstTagValue('var');
					$collection = new $collectionName();
				}
				$this->collection = $collection;
			}
		}
		// after collection is made, to run getGridColumns
		$allowEdit = $this->request->getControllerString() == get_class($this);
		$this->setColumns(get_class($this->collection), $allowEdit);
	}

	/**
	 * Can't use $this->collection at this point as this function is used to initialize the collection!
	 * @return string
	 */
	function getOrderBy() {
		$ret = '';
		$sortBy = $this->sort['sortBy'];
		if ($this->model &&
			$this->model->thes &&
			is_array($this->model->thes[$sortBy]) &&
			ifsetor($this->model->thes[$sortBy]['source'])) {
			$sortBy = $this->model->thes[$sortBy]['source'];
		}
		if ($this->collection &&
			$this->collection->thes) {
			$desc = ifsetor($this->collection->thes[$sortBy]);
			//debug(array_keys($this->collection->thes), $desc);
			if (is_array($desc) &&
				ifsetor($desc['source']) &&
				ifsetor($desc['sortable']) !== false) {
				$sortBy = $desc['source'];
			}
			if (ifsetor($desc['sortable']) === false) {
				$sortBy = NULL;
			}
		}
		$sortBy = $sortBy ? $sortBy : ifsetor($this->model->idField);
		if ($sortBy) {
			$ret = 'ORDER BY '.$this->db->quoteKey($sortBy).' '.
				(ifsetor($this->sort['sortOrder']) ? 'DESC' : 'ASC');
		}
		return $ret;
	}

	function render() {
		$this->setVisibleColumns();
		//$this->collection->pageSize = $this->pageSize;
		return parent::render();
	}

	function setVisibleColumns() {
		if ($this->columns) {
			foreach ($this->collection->thes as $cn => $_) {
				if (!$this->columns->isVisible($cn)) {
					//unset($this->collection->thes[$cn]);
					$this->collection->thes[$cn]['!show'] = true;
				}
			}
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	function getFilterWhere()
	{
		return $this->filterController->getFilterWhere(
			$this->getFilterDesc());
	}

	function sidebar() {
		$fields = $this->collection->thes;
		$content[] = $this->getFilterForm($fields);
		$content[] = $this->getColumnsForm();
		return $content;
	}

	/**
	 * @param array $fields
	 * @return array|HTMLFormTable
	 * @throws Exception
	 */
	function getFilterForm(array $fields = []) {
		if (method_exists($this, 'getFilterDesc')) {
			$this->filterController->desc = $this->getFilterDesc($fields);
		} else {
			$fields = $fields ?: $this->collection->thes;
			$this->filterController->setFields($fields);
		}
		$this->filterController->linkVars['c'] = get_class($this);
		return $this->filterController->render();
	}

	/**
	 * Make sure you fill the 'value' fields with data from $this->filter manually.
	 * Why manually? I don't know, it could change.
	 *
	 * @param array $fields
	 * @throws Exception
	 * @return array
	 */
	function getFilterDesc(array $fields = NULL) {
		return $this->filterController->getFilterDesc($fields);
	}

	function getColumnsForm() {
//		debug($this->getGridColumns());
//		debug($this->columns->getData());
		$desc = array(
			'columns' => array(
				'label' => '<h2>'.__('Visible').'</h2>',
				'type' => 'keyset',
				'options' => $this->getGridColumns(),
				'value' => $this->columns->getData(),
				'between' => '',
			),
			'collectionName' => array(
				'type' => 'hidden',
				'value' => get_class($this->collection),
			)
		);
		$f = new HTMLFormTable();
		$f->method('GET');
		$f->defaultBR = true;
		$f->formHideArray($this->linkVars);
		$f->showForm($desc);
		$f->submit(__('Set Visible Columns'));
		return $f;
	}

	/**
	 * @throws Exception
	 */
	function injectCollection() {
		parent::injectCollection();
		debug($this->collection->where,
			$this->getFilterWhere());
		$this->collection->where = array_merge(
			$this->collection->where,
			$this->getFilterWhere()
		);
	}

}
