<?php

class FullGrid extends Grid {

	public $columns;

	function __construct() {
		parent::__construct();
		if ($this->request->is_set('columns')) {
			$cn = $this->request->getTrim('collectionName');
			$this->user->setPref('Columns.'.$cn, $this->request->getArray('columns'));
		}
	}

	function render() {
		$this->columns = $this->collection->thes;
		$setColumns = $this->user->getPref('Columns.'.get_class($this->collection));
		if ($setColumns) {
			foreach ($this->collection->thes as $cn => $_) {
				if (!in_array($cn, $setColumns)) {
					unset($this->collection->thes[$cn]);
				}
			}
		}
		return parent::render();
	}

	function getFilterWhere() {
		$where = array();
		$filter = $this->request->getArray('filter');

		foreach ($filter as $key => $val) {
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

		$filter = $this->request->getSubRequest('filter');

		$desc = array();
		foreach ($fields as $key => $val) {
			$desc[$key] = array(
				'label' => $key,
				'type' => 'select',
				'options' => $this->getTableFieldOptions($key),
				'null' => true,
				'value' => $filter->getTrim($key),
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

	function getTableFieldOptions($key) {
		return Config::getInstance()->qb->getTableOptions($this->model->table,
		$key, array(), 'ORDER BY '.$this->db->quoteKey($key), $key);

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
		foreach ($this->columns as &$val) {
			$val = is_array($val) ? $val['name'] : $val;
		}

		$checked = $this->user->getPref('Columns.'.get_class($this->collection));
		if (!$checked) {
			$checked = array_keys($this->columns);
		}

		$desc = array(
			'columns' => array(
				'label' => 'Visible',
				'type' => 'set',
				'options' => $this->columns,
				'value' => $checked,
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
