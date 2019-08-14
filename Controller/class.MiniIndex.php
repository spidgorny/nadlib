<?php

class MiniIndex extends AppController
{

	/**
	 * @var Menu
	 */
	public $menu;

	/**
	 * @var AppController
	 */
	public $controller;

	/**
	 * @var MiniIndex
	 */
	protected static $instance;

	public $header = array();
	public $footer = array();

	public $layout;

	/**
	 * @var Config
	 */
	public $config;

	public function __construct()
	{
		parent::__construct();
		$this->config = Config::getInstance();
	}

	/**
	 * Set $createAllowed to true only in index.php when making the original Index
	 * all other situation will return NULL if it's not instantiated yet
	 * thus avoiding infinite loops.
	 * @param bool $createAllowed
	 * @return MiniIndex
	 */
	public static function getInstance($createAllowed = true)
	{
		$self = get_called_class();
		if (!self::$instance) {
			if ($createAllowed) {
				self::$instance = new $self(true);
				/** @var self::$instance MiniIndex */
				self::$instance->init();
			}
		}
		return self::$instance;
	}

	function init()
	{
		$this->controller = $this->request->getController();
		//debug(get_class($this), spl_object_hash($this));
		//debug(get_class($this->controller), spl_object_hash($this->controller));
		if (method_exists($this->controller, 'postInit')) {
			$this->controller->postInit();
		}
	}

	function render()
	{
		if ($this->controller->layout == 'none' || $this->request->isAjax()) {
			$content = $this->renderController();
		} else {
			$v = new View('template.phtml', $this);
			$v->content = $this->renderController();
			$v->sidebar = $this->showSidebar();
			$v->baseHref = $this->request->getLocation();
			$this->title = $this->controller->title;    // after $controller->render() before $view->render()
			$content = $v->render();
		}
		return $content;
	}

	function renderController()
	{
		$content = '';
		if ($this->controller) {
			try {
				$content = $this->controller->render();
			} catch (Exception $e) {
				$content = $this->error($e->getMessage());
			}
			if (!$this->request->isAjax() && $this->controller->layout instanceof Wrap) {
				$content = $this->controller->layout->wrap($content);
			}
		}
		return $content;
	}

	function message($text)
	{
		return '<div class="message">' . $text . '</div>';
	}

	function error($text)
	{
		return '<div class="ui-state-error alert alert-error alert-danger padding">' . $text . '</div>';
	}

	function addJQuery()
	{
		$this->footer['jquery.js'] = '
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.8.1.min.js"><\/script>\')</script>
		';
	}

	function addJQueryUI()
	{
		$this->addJQuery();
		$this->footer['jqueryui.js'] = '<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>';
		$this->addCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/base/jquery-ui.css');
	}

	function addJS($source)
	{
		$this->footer[$source] = '<script src="' . $source . '"></script>';
	}

	function addCSS($source)
	{
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="' . $source . '" />';
	}

	function showSidebar()
	{
		if (method_exists($this->controller, 'sidebar')) {
			$content = $this->controller->sidebar();
		}
		return $content;
	}

	function renderProfiler()
	{
		$profiler = $GLOBALS['profiler'];
		/** @var $profiler TaylorProfiler */
		if ($profiler) {
			$content = $profiler->renderFloat();
			$content .= $profiler->printTimers(true);
		} else if (DEVELOPMENT) {
			$content = TaylorProfiler::renderFloat();
		}
		return $content;
	}

}
