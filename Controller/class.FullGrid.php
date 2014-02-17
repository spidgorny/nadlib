<?php

abstract class FullGrid extends Grid {

	/**
	 * @param string $collection
	 */
	function __construct($collection = NULL) {
		parent::__construct();

		// menu is making an instance of each class because of tryMenuSuffix
		//debug(get_class($this->index->controller), get_class($this), $this->request->getControllerString());
		//if (get_class($this->index->controller) == get_class($this)) {// unreliable
		if ($this->request->getControllerString() == get_class($this)) {
			$this->saveFilterColumnsSort($collection ? $collection : get_class($this));
		}
		if ($collection) {
			$this->collection = new $collection(-1, $this->getFilterWhere(), $this->getOrderBy());
			$this->collection->postInit();
			$this->collection->pager = new Pager($this->pageSize ? $this->pageSize->get() : NULL);
		}
	}

	function postInit() {
		if ($this->collection) {
			$this->collection->retrieveDataFromDB();
		}
	}

	/**
	 * Can't use $this->collection at this point as this function is used to initialize the collection!
	 * @return string
	 */
	function getOrderBy() {
		$sortBy = $this->sort['sortBy'];
		if ($this->model->thes && is_array($this->model->thes[$sortBy]) && $this->model->thes[$sortBy]['source']) {
			$sortBy = $this->model->thes[$sortBy]['source'];
		}
		$sortBy = $sortBy ? $sortBy : $this->model->idField;
		if ($sortBy) {
			$ret = 'ORDER BY '.$this->db->quoteKey($sortBy).' '.($this->sort['sortOrder'] ? 'DESC' : 'ASC');
		}
		return $ret;
	}

	function render() {
		if ($this->columns) {
			foreach ($this->collection->thes as $cn => $_) {
				if (!in_array($cn, $this->columns)) {
					//unset($this->collection->thes[$cn]);
					$this->collection->thes[$cn]['!show'] = true;
				}
			}
		}
		//$this->collection->pageSize = $this->pageSize;
		return parent::render();
	}

	/**
	 * Converts $this->filter data from URL into SQL where parameters
	 * @return array
	 */
	function getFilterWhere() {
		$where = array();

		foreach ($this->filter as $key => $val) {
			if ($val) {
				$where[$key] = $val;
			}
		}

		return $where;
	}

	function sidebar() {
		$content = $this->getFilterForm();
		$content .= $this->getColumnsForm();
		return $content;
	}

	function getFilterForm(array $fields = NULL) {
		$f = new HTMLFormTable();
		$f->method('GET');
		$f->defaultBR = true;
		$f->formHideArray($this->linkVars);
		$f->prefix('filter');
		$f->showForm($this->getFilterDesc($fields));
		$f->prefix(NULL);
		$f->submit('Filter');
		return $f;
	}

	/**
	 * Make sure you fill the 'value' fields with data from $this->filter manually.
	 * Why manually? I don't know, it could change.
	 *
	 * @param array $fields
	 * @return array
	 */
	function getFilterDesc(array $fields = NULL) {
		$fields = $fields ? $fields : $this->model->thes;
		$fields = $fields ? $fields : $this->collection->thes;
		$fields = is_array($fields) ? $fields : array();

		//debug($this->filter);
		$desc = array();
		foreach ($fields as $key => $k) {
			if (!$k['noFilter']) {
				$autoClass = ucfirst(str_replace('id_', '', $key)).'Collection';
				if (class_exists($autoClass) &&
					in_array('HTMLFormCollection', class_implements($autoClass))) {
					$type = new $autoClass();
					$options = NULL;
				} elseif ($k['tf']) {	// boolean
					$type = 'select';
					$stv = new slTableValue('', array());
					$options = array(
						't' => $stv->SLTABLE_IMG_CHECK,
						'f' => $stv->SLTABLE_IMG_CROSS,
					);
					//debug($key, $this->filter[$key]);
				} else {
					$type = 'select';
					$options = $this->getTableFieldOptions($k['dbField'] ? $k['dbField'] : $key, false);
					$options = ArrayPlus::create($options)->trim()->getData();	// convert to string for === operation
					//debug($options);
					$options = array_combine_stringkey($options, $options); // will only work for strings, ID to other table needs to avoid it
					//debug($options);
				}
				$desc[$key] = array(
					'label' => $k['name'],
					'type' => $type,
					'options' => $options,
					'null' => true,
					'value' => $this->filter[$key],
					'more' => 'class="input-medium"',
					'===' => true,
				);
			}
		}
		//debug($fields, $desc);
		return $desc;
	}

	function getTableFieldOptions($key, $count = false) {
		$res = $this->db->getTableOptions($this->model->table
			? $this->model->table
			: $this->collection->table,
		$key, array(), 'ORDER BY title', $this->model->idField);

		if ($count) {
			foreach ($res as &$val) {
				$copy = clone $this->collection;
				$copy->where[$key] = $val;
				$copy->retrieveDataFromDB();
				$val .= ' ('.sizeof($copy->data).')';
			}
		}

		return $res;
	}

	function getColumnsForm() {
		$desc = array(
			'columns' => array(
				'label' => 'Visible<br />',
				'type' => 'set',
				'options' => ArrayPlus::create($this->collection->thes)->column('name')->getData(),
				'value' => $this->columns,
				'between' => '<br />',
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
		$f->submit('Set');
		return $f;
	}

}
