<?php

abstract class FullGrid extends Grid {

	/**
	 * @var FilterController
	 */
	var $filterController;

	/**
	 * @param string $collection
	 */
	function __construct($collection = NULL) {
		parent::__construct();

		$this->filterController = new FilterController();

		// menu is making an instance of each class because of tryMenuSuffix
		//debug(get_class($this->index->controller), get_class($this), $this->request->getControllerString());
		$allowEdit = $this->request->getControllerString() == get_class($this);
		if ($allowEdit && $collection) {
			$this->saveFilterAndSort($collection ?: get_class($this));
		}
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
				$this->collection = $collection;
			}
		}
		// after collection is made, to run getGridColumns
		$this->setColumns(get_class($this->collection), $allowEdit);
	}

	function postInit() {
		if ($this->collection) {
			// commented to do it in a lazy way
			//$this->collection->retrieveData();
		}
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

	function getFilterWhere()
	{
		return $this->filterController->getFilterWhere();
	}

	function sidebar() {
		$content[] = $this->getFilterForm();
		$content[] = $this->getColumnsForm();
		return $content;
	}

	function getFilterForm(array $fields = NULL) {
		$this->filterController->setFields($fields);
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

	function getTableFieldOptions($key, $count = false) {
		if ($this->model instanceof OODBase) {
			$res = $this->db->getTableOptions($this->model->table
				? $this->model->table
				: $this->collection->table,
				$key, array(), 'ORDER BY title', $this->model->idField);

			if ($count) {
				foreach ($res as &$val) {
					/** @var Collection $copy */
					$copy = clone $this->collection;
					$copy->where[$key] = $val;
					$copy->retrieveData();
					$val .= ' (' . sizeof($copy->getData()) . ')';
				}
			}
		} else {
			$res = [];
		}

		return $res;
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
