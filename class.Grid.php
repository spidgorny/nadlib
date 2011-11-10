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
		//$this->model = new OODBase();
		//$this->collection = new Collection();
	}

}
