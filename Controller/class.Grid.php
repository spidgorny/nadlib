<?php

abstract class Grid extends Controller {
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

		// do this in a subclass
		//$this->model = new OODBase();
		//$this->collection = new Collection();
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
	 * Take from preferences and then append/overwrite from URL
	 * How does it work when some params need to be cleared?
	 */
	function mergeRequest($subname = NULL) {
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
		$content = $this->collection;
		$content = $this->encloseInAA($content, $this->title = $this->title ? $this->title : get_class($this));
		return $content;
	}

}
