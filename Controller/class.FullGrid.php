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

	/**
	 * @var PageSize
	 */
	public $pageSize;

	function __construct($collection) {
		parent::__construct();
		$this->saveFilterColumnsSort(get_class($this));
		$this->collection = new $collection(-1, $this->getFilterWhere(), $this->getOrderBy());
		$this->collection->pager = new Pager($this->pageSize->get());
		$this->collection->retrieveDataFromDB();
	}

	/**
	 * @param null $cn Supply get_class($this) to the function
	 * 					or it should be called after $this->collection is initialized
	 */
	function saveFilterColumnsSort($cn = NULL) {
		$cn = $cn ? $cn : get_class($this->collection);
		//debug($cn);
		if ($this->request->is_set('columns')) {
			$this->user->setPref('Columns.'.$cn, $this->request->getArray('columns'));
		}
		$this->columns = $this->request->getArray('columns');
		$this->columns = $this->columns
			? $this->columns
			: $this->user->getPref('Columns.'.$cn);
		if (!$this->columns && $this->model->thes) {
			$this->columns = array_keys($this->model->thes);
		}
		if (!$this->columns && $this->collection->thes) {
			$this->columns = array_keys($this->collection->thes);
		}

		$this->filter = $this->request->getArray('filter');
		$this->filter = $this->filter
			? $this->filter
			: $this->user->getPref('Filter.'.$cn);
		$this->filter = $this->filter ? $this->filter : array();
		//debug($this->filter);
		$this->user->setPref('Filter.'.$cn, $this->filter);

		if ($this->request->is_set('slTable')) {
			$this->user->setPref('Sort.'.$cn, $this->request->getArray('slTable'));
		}
		$sortRequest = $this->request->getArray('slTable');
		$this->sort = $sortRequest
			? $sortRequest
			: ($this->user->getPref('Sort.'.$cn) ?: $this->sort);

		$this->pageSize = new PageSize();
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
		$f->formHideArray('', $this->linkVars);
		$f->prefix('filter');
		$f->showForm($this->getFilterDesc($fields));
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
				$options = $this->getTableFieldOptions($k['dbField'] ? $k['dbField'] : $key, false);
				$options = AP($options)->trim()->getData();	// convert to string for === operation
				debug($options);
				$options = array_combine_stringkey($options, $options); // will only work for strings, ID to other table needs to avoid it
				debug($options, array_keys($options), $this->filter['partitions']);
				$desc[$key] = array(
					'label' => $k['name'],
					'type' => 'select',
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
		$res = Config::getInstance()->qb->getTableOptions($this->model->table ? $this->model->table : $this->collection->table,
		$key, array(), 'ORDER BY title');

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
				'label' => 'Visible',
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
		$f->formHideArray('', $this->linkVars);
		//$f->prefix('columns');
		$f->showForm($desc);
		$f->submit('Set');
		return $f;
	}

}
