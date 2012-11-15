<?php

abstract class Grid extends AppController {
	/**
	 *
	 * @var Collection
	 */
	protected $collection;

	/**
	 *
	 * @var Model
	 */
	protected $model;

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

	function render() {
		$content = $this->collection->render();
		$content = $this->encloseInAA($content, $this->title = $this->title ? $this->title : get_class($this));
		return $content;
	}

}
