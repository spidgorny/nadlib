<?php

class MiniIndex extends Controller {

	/**
	 * @var Menu
	 */
	public $menu;

	/**
	 * @var Controller
	 */
	public $controller;

	/**
	 * @var MiniIndex
	 */
	protected static $instance;

	public $header = array();
	public $footer = array();

	public $layout;

	function __construct() {
		parent::__construct();
		$this->controller = $this->request->getController();
	}

	public static function getInstance($createAllowed = true) {
		$self = get_called_class();
		return self::$instance ?: ($createAllowed
			? self::$instance = new $self
			: NULL
		);
	}

	function render() {
		if ($this->controller->layout == 'none' || $this->request->isAjax()) {
			return $this->renderController();
		} else {
			return new View('template.phtml', $this);
		}
	}

	function renderController() {
		if ($this->controller) {
			return $this->controller->render();
		}
	}

	function addJQuery() {
		$this->footer['jquery.js'] = '<script src="nadlib/js/jquery-1.8.1.min.js"></script>';
	}

	function addJS($source) {
		$this->footer[$source] = '<script src="'.$source.'"></script>';
	}

	function addCSS($source) {
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="'.$source.'" />';
	}

	function showSidebar() {
		if (method_exists($this->controller, 'sidebar')) {
			$content = $this->controller->sidebar();
		}
		return $content;
	}

}
