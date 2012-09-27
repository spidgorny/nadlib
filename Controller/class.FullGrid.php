<?php

class FullGrid extends Grid {
	/**
	 * @var array
	 */
	public $filter;

	/**
	 * @var array
	 */
	public $columns;

	/**
	 * @var array
	 */
	public $sort;

	function __construct() {
		parent::__construct();
	}

	function saveFilterColumnsSort($cn = NULL) {
		$cn = $cn ? $cn : get_class($this->collection);
		if ($this->request->is_set('columns')) {
			$this->user->setPref('Columns.'.$cn, $this->request->getArray('columns'));
		}
		$this->columns = $this->request->getArray('columns');
		$this->columns = $this->columns
			? $this->columns
			: $this->user->getPref('Columns.'.$cn);
		if (!$this->columns) {
			$this->columns = array_keys($this->model->thes);
		}

		if ($this->request->is_set('filter')) {
			$this->user->setPref('Filter.'.$cn, $this->request->getArray('filter'));
		}
		$this->filter = $this->request->getArray('filter');
		$this->filter = $this->filter
			? $this->filter
			: $this->user->getPref('Filter.'.$cn);
		$this->filter = $this->filter ? $this->filter : array();

		if ($this->request->is_set('slTable')) {
			$this->user->setPref('Sort.'.$cn, $this->request->getArray('slTable'));
		}
		$this->sort = $this->request->getArray('slTable');
		$this->sort = $this->sort
			? $this->sort
			: $this->user->getPref('Sort.'.$cn);
		//$_REQUEST['slTable'] = $this->sort;	// influence slTable
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
		$ret = 'ORDER BY '.$this->db->quoteKey($sortBy).' '.($this->sort['sortOrder'] ? 'DESC' : 'ASC');
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
		return parent::render();
	}

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
		$fields = $fields ? $fields : $this->model->data;

		$desc = array();
		foreach ($fields as $key => $val) {
			$desc[$key] = array(
				'label' => $key,
				'type' => 'select',
				'options' => $this->getTableFieldOptions($key, true),
				'null' => true,
				'value' => $this->filter[$key],
			);
		}

		$f = new HTMLFormTable();
		$f->method('GET');
		$f->defaultBR = true;
		$f->prefix('filter');
		$f->showForm($desc);
		$f->submit('Filter');
		return $f;
	}

	function getTableFieldOptions($key, $count = false) {
		$res = Config::getInstance()->qb->getTableOptions($this->model->table,
		$key, array(), 'ORDER BY '.$this->db->quoteKey($key), $key);

		if ($count) {
			foreach ($res as &$val) {
				$copy = clone $this->collection;
				$copy->where[$key] = $val;
				$copy->retrieveDataFromDB();
				$val .= ' ('.sizeof($copy->data).')';
			}
		}

		return $res;

		/*$options = $this->db->fetchSelectQuery($this->model->table, array(),
			'GROUP BY '.$this->db->quoteKey($key),
			'DISTINCT '.$this->db->quoteKey($key), true);
		$res = array();
		foreach ($options as $row) {
			$res[$row[$key]] = $row[$key];
		}
		return $res;*/
	}

	function getColumnsForm() {
		foreach ($this->collection->thes as &$val) {
			$val = is_array($val) ? $val['name'] : $val;
		}

		$desc = array(
			'columns' => array(
				'label' => 'Visible',
				'type' => 'set',
				'options' => $this->collection->thes,
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
		//$f->prefix('columns');
		$f->showForm($desc);
		$f->submit('Set');
		return $f;
	}

}
