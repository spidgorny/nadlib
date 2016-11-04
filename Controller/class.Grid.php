<?php

abstract class Grid extends AppController {

	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @var OODBase
	 */
	protected $model;

	/**
	 * @var array
	 */
	public $filter = array();

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

	function __construct() {
		parent::__construct();
	}

	/**
	 * Either take from URL or take from preferences, not both
	 */
	function getSetRequest() {
		if ($this->request->getAll()) {
			$this->user->setPref(get_class($this).'.Request', $this->request);
		} else {
			$maybe = $this->user->getPref(get_class($this).'.Request');
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
	 * @deprecated - use saveFilterColumnsSort() instead
	 *
	 */
	function mergeRequest($subname = NULL) {
		//echo '<div class="error">'.__METHOD__.get_class($this).'</div>';
		if ($subname) {
			$r = $this->request->getSubRequest($subname);
		} else {
			$r = $this->request;
		}
		$default = $this->user->getPref(get_class($this).'.Request');
		if ($default instanceof Request) {
			$r->append($default->getAll());
		}
		$this->user->setPref(get_class($this).'.Request', $r);
		if ($subname) {
			$this->request->set($subname, $r->getAll());
		}
	}

	/**
	 * @param null $cn Supply get_class($this) to the function
	 * 					or it should be called after $this->collection is initialized
	 */
	function saveFilterColumnsSort($cn = NULL) {
		$cn = $cn ? $cn : get_class($this->collection);
		//debug($cn);
		assert($cn);

		$allowEdit = $this->request->getControllerString() == get_class($this);

		if ($this->request->is_set('columns') && $allowEdit) {
			$this->user->setPref('Columns.'.$cn, $this->request->getArray('columns'));
		}
		$this->columns = $allowEdit
			? $this->request->getArray('columns')
			: array();
		if (method_exists($this->user, 'getPref')) {
			$this->columns = $this->columns
				? $this->columns
				: $this->user->getPref('Columns.'.$cn);
		}
		if (!$this->columns && $this->model->thes) {
			$this->columns = array_keys($this->model->thes);
		}
		if (!$this->columns && $this->collection->thes) {
			$this->columns = array_keys($this->collection->thes);
		}

		/**
		 * Only get filter if it's not need to be cleared
		 */
		if ($this->request->getTrim('action') == 'clearFilter' && $allowEdit) {
		} else {
			$this->filter = $allowEdit ? $this->request->getArray('filter') : array();
			//d($this->request->getControllerString(), get_class($this), $allowEdit, $this->filter);
			if (method_exists($this->user, 'getPref')) {
				$this->filter = $this->filter
					? $this->filter
					: $this->user->getPref('Filter.'.$cn);
			}
			$this->filter = $this->filter ? $this->filter : array();
			//debug(get_class($this), 'Filter.'.$cn, $this->filter);
		}
		//debug(spl_object_hash(Index::getInstance()->controller), spl_object_hash($this));
		//if (Index::getInstance()->controller == $this) {	// Menu may make instance of multiple controllers

		if (method_exists($this->user, 'setPref')) {
			if ($allowEdit) {
				$this->user->setPref('Filter.'.$cn, $this->filter);
			}

			if ($this->request->is_set('slTable') && $allowEdit) {
				$this->user->setPref('Sort.'.$cn, $this->request->getArray('slTable'));
			}
		}
		$sortRequest = $this->request->getArray('slTable');
		if (method_exists($this->user, 'getPref')) {
			$this->sort = $sortRequest
				? $sortRequest
				: ($this->user->getPref('Sort.'.$cn)
					? $this->user->getPref('Sort.'.$cn)
					: $this->sort
				);
		}

		$this->pageSize = $this->pageSize ? $this->pageSize : new PageSize();
	}

	function render() {
		$content = $this->collection->render();
		$content .= '<hr />';
		$content = $this->encloseInAA($content, $this->title = $this->title ? $this->title : get_class($this), $this->encloseTag);
		return $content;
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

	function sidebar() {
		$content = $this->collection->showFilter();
		return $content;
	}

}
