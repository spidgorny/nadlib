<?php

class MiniIndex extends AppControllerBE
{

	/**
	 * @var ?MiniIndex
	 */
	protected static $miniIndex;

	/**
	 * @var Menu
	 */
	public $menu;

	/**
	 * @var AppControllerBE|SimpleController|null
	 */
	public $controller;

	public $header = [];

	public $footer = [];

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
	 * @return MiniIndex
	 */
	public static function getInstance()
	{
		if (!self::$miniIndex) {
			throw new RuntimeException('MiniIndex is not instantiated yet. Please instantiate it in index.php');
		}

		return self::$miniIndex;
	}

	public static function createInstance(): MiniIndex
	{
		$self = static::class;
		self::$miniIndex = new $self();
		self::$miniIndex->init();
		return self::$miniIndex;
	}

	public function init(): void
	{
		$this->controller = $this->request->getController();
		//debug(get_class($this), spl_object_hash($this));
		//debug(get_class($this->controller), spl_object_hash($this->controller));
		if (method_exists($this->controller, 'postInit')) {
			$this->controller->postInit();
		}
	}

	public function render()
	{
		if (($this->controller instanceof AppControllerBE && property_exists($this->controller, 'layout') && $this->controller->layout === 'none') || $this->request->isAjax()) {
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

	public function renderController()
	{
		$content = '';
		if ($this->controller) {
			try {
				$content = $this->controller->render();
			} catch (Exception $e) {
				$content = $this->error($e->getMessage());
			}

			if (!$this->request->isAjax()) {
				if ($this->controller instanceof Controller &&
					property_exists($this->controller, 'layout') && $this->controller->layout instanceof Wrap) {
					$content = $this->controller->layout->wrap($content);
				}
			}
		}

		return $content;
	}

	public function error($text): string
	{
		return '<div class="ui-state-error alert alert-error alert-danger padding">' . $text . '</div>';
	}

	public function showSidebar()
	{
		if (method_exists($this->controller, 'sidebar')) {
			return $this->controller->sidebar();
		}

		return null;
	}

	public function message($text): string
	{
		return '<div class="message">' . $text . '</div>';
	}

	public function addJQueryUI(): void
	{
		$this->addJQuery();
		$this->footer['jqueryui.js'] = '<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>';
		$this->addCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/base/jquery-ui.css');
	}

	public function addJQuery(): void
	{
		$this->footer['jquery.js'] = '
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script>window.jQuery || document.write(\'<script src="vendor/jquery/jquery.min.js"><\/script>\')</script>
		';
	}

	public function addCSS(string $source): void
	{
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="' . $source . '" />';
	}

	public function addJS(string $source): void
	{
		$this->footer[$source] = '<script src="' . $source . '"></script>';
	}

	public function renderProfiler(): string|false
	{
		$content = '';
		$profiler = $GLOBALS['profiler'];
		/** @var ?TaylorProfiler $profiler */
		if ($profiler) {
			$content = $profiler->renderFloat();
			$content .= $profiler->printTimers(true);
		} elseif (DEVELOPMENT) {
			$content = TaylorProfiler::renderFloat();
		}

		return $content;
	}

}
