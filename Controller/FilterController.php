<?php

class FilterController extends AppController
{

	public $fields = [];

	/**
	 * @var Filter
	 */
	public $filter;

	/**
	 * @var OODBase - used to retrieve options for a specific db field
	 */
	public $model;

	/**
	 * Alternative function to getFilterDesc()
	 * @var callable
	 */
	public $injectFilterDesc;

	/**
	 * @var array
	 */
	public $desc;

	function setFields(array $fields)
	{
		$this->fields = $fields;
		$this->desc = $this->getFilterDesc($this->fields);
	}

	function setFilter(Filter $filter)
	{
		$this->filter = $filter;
	}

	function render()
	{
		$f = new HTMLFormTable($this->desc);
		$f->setAllOptional();
		$f->method('POST');
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
	 * @return array
	 * @throws Exception
	 */
	function getFilterDesc(array $fields = NULL)
	{
//		if (is_callable($this->injectFilterDesc)) {
//			return call_user_func($this->injectFilterDesc);
//		}
		$fields = ifsetor($fields, ifsetor($this->model->thes));
		$fields = is_array($fields) ? $fields : $this->fields;

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

	/**
	 * @param $k
	 * @param $key
	 * @return array
	 */
	public function getFieldFilter(array $k, $key)
	{
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
				// will only work for strings, ID to other table needs to avoid it
				$options = array_combine_stringkey($options, $options);
			} else {
				$options = $k['options'];
			}
			//debug($options);
		} elseif (ifsetor($k['type']) == 'like') {
			// this is handled in getFilterWhere
			$options = NULL;
		} else {
			$k['type'] = $k['type'] ?: 'input';
			$options = NULL;
		}
		$k = array(
				'label' => $k['name'],
				'type' => $k['type'] ?: 'text',
				'options' => $options,
				'null' => true,
				'value' => isset($this->filter[$key]) ? $this->filter[$key] : ifsetor($k['value']),
				'more' => ['class' => "text input-medium"],
				'===' => true,
			) + $k;
//		debug(without($k, 'options'));
		return $k;
	}

	function getTableFieldOptions($key, $count = false)
	{
		if ($this->model instanceof OODBase) {
			$res = $this->db->getTableOptions($this->model->table
				? $this->model->table
				: $this->collection->table,
				$key, array(), 'ORDER BY ' . $key, $key);    // NOT 'id' (DISTINCT!)

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
//		debug(__METHOD__, $res, )

		return $res;
	}

	/**
	 * Converts $this->filter data from URL into SQL where parameters
	 * @return array
	 */
	function getFilterWhere(array $desc)
	{
		$where = array();

		$filterList = $this->filter->getIterator();
//		debug(gettype2($this->injectFilterDesc), count($desc),
//			$this->filter->getArrayCopy(), $filterList->getArrayCopy()); exit();
		foreach ($filterList as $key => $val) {
//			debug($key, $val);
			if ($val) {
				$type = ifsetor($desc[$key]['type']);
				list($field, $parameter) = $this->getFilterWherePair($key, $val, $type);
				$where[$field] = $parameter;
			}
		}

		return $where;
	}

	function getFilterWherePair($key, $val, $type)
	{
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

}
