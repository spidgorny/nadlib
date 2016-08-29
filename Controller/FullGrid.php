<?php

abstract class FullGrid extends Grid {

	/**
	 * @param string $collection
	 */
	function __construct($collection = NULL) {
		parent::__construct();

		// menu is making an instance of each class because of tryMenuSuffix
		//debug(get_class($this->index->controller), get_class($this), $this->request->getControllerString());
		$allowEdit = $this->request->getControllerString() == get_class($this);
		if ($allowEdit && $collection) {
			$this->saveFilterColumnsSort($collection ? $collection : get_class($this));
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
				if (!in_array($cn, $this->columns)) {
					//unset($this->collection->thes[$cn]);
					$this->collection->thes[$cn]['!show'] = true;
				}
			}
		}
	}

	/**
	 * Converts $this->filter data from URL into SQL where parameters
	 * @return array
	 */
	function getFilterWhere() {
		$where = array();

		$desc = $this->getFilterDesc();
		//debug($desc);
		foreach ($this->filter as $key => $val) {
			if ($val) {
				$type = ifsetor($desc[$key]['type']);
				list($field, $parameter) = $this->getFilterWherePair($key, $val, $type);
				$where[$field] = $parameter;
			}
		}

		return $where;
	}

	function getFilterWherePair($key, $val, $type) {
		$where = [];
		switch ($type) {
			case 'like':
				$like = new SQLLikeContains($val);
				$where[$key] = $like;
				break;
			default:
				$where[$key] = $val;
				break;
		}
		return [$key, $val];
	}

	function sidebar() {
		$content[] = $this->getFilterForm();
		$content[] = $this->getColumnsForm();
		return $content;
	}

	function getFilterForm(array $fields = NULL) {
		$desc = $this->getFilterDesc($fields);
		$f = new HTMLFormTable($desc);
		$f->setAllOptional();
		$f->method('GET');
		$f->defaultBR = true;
		$f->formHideArray($this->linkVars);
		$f->prefix('filter');
		$f->showForm();
		$f->prefix(NULL);
		$f->submit(__('Filter'));
		return $f;
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
		$fields = ifsetor($fields, ifsetor($this->model->thes));
		$fields = $fields ?: $this->collection->thes;
		$fields = is_array($fields) ? $fields : array();

		//debug($this->filter);
		$desc = array();
		foreach ($fields as $key => $k) {
			if (!is_array($k)) {
				$k = ['name' => $k];
			}
			if (!ifsetor($k['noFilter'])) {
				$desc[$key] = $this->getFieldFilter($k, $key);
			}
		}
		//debug($fields, $desc);
		return $desc;
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
//		debug($this->columns);
		$desc = array(
			'columns' => array(
				'label' => '<h2>'.__('Visible').'</h2>',
				'type' => 'set',
				'options' => $this->getGridColumns(),
				'value' => $this->columns
					? $this->columns : [],
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
		//$f->prefix('columns');
		$f->showForm($desc);
		$f->submit(__('Set'));
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

	/**
	 * @param $k
	 * @param $key
	 * @return array
	 */
	public function getFieldFilter($k, $key) {
		$autoClass = ucfirst(str_replace('id_', '', $key)) . 'Collection';
		if (class_exists($autoClass) &&
			in_array('HTMLFormCollection', class_implements($autoClass))
		) {
			$k['type'] = new $autoClass();
			$options = NULL;
		} elseif (ifsetor($k['tf'])) {    // boolean
			$k['type'] = 'select';
			$stv = new slTableValue('', array());
			$options = array(
				't' => $stv->SLTABLE_IMG_CHECK,
				'f' => $stv->SLTABLE_IMG_CROSS,
			);
			//debug($key, $this->filter[$key]);
		} elseif (ifsetor($k['type']) == 'select') {
			if (!isset($k['options'])) {    // NOT ifsetor as we want to accept empty
				$options = $this->getTableFieldOptions(ifsetor($k['dbField'], $key), false);
				// convert to string for === operation
				$options = ArrayPlus::create($options)->trim()->getData();
			} else {
				$options = $k['options'];
			}
			//debug($options);
			// will only work for strings, ID to other table needs to avoid it
			$options = array_combine_stringkey($options, $options);
			//debug($options);
		} elseif (ifsetor($k['type']) == 'like') {
			// this is handled in getFilterWhere
			$options = NULL;
		} else {
			$k['type'] = $k['type'] ?: 'input';
			$options = NULL;
		}
		return array(
			'label'   => $k['name'],
			'type'    => $k['type'],
			'options' => $options,
			'null'    => true,
			'value'   => ifsetor($this->filter[$key]),
			'more'    => 'class="input-medium"',
			'==='     => true,
		);
	}

}
