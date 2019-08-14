<?php

class IndexBase /*extends Controller*/
{    // infinite loop

	/**
	 * @var MySQL
	 */
	public $db;

	/**
	 * @var LocalLangDummy
	 */
	public $ll;

	/**
	 * @var User|LoginUser
	 */
	public $user;

	/**
	 * For any error messages during initialization.
	 *
	 * @var string|array
	 */
	public $content;

	/**
	 * @var AppController
	 */
	public $controller;

	/**
	 * @var Index|IndexBE
	 */
	protected static $instance;

	public $header = array();

	public $footer = array();

	public $loadJSfromGoogle = true;

	public $template = 'template.phtml';

	public function __construct()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($_REQUEST['d'] == 'log') echo __METHOD__ . '#' . __LINE__ . BR;
		//parent::__construct();
		$config = Config::getInstance();
		$this->db = $config->db;
		$this->ll = new LocalLangDummy();    // the real one is in Config!

		$this->request = Request::getInstance();
		//debug('session_start');

		// only use session if not run from command line
		if (!Request::isCLI() && !session_id() /*&& session_status() == PHP_SESSION_NONE*/) {
			session_start();
		}

		$this->user = $config->user;
		$this->restoreMessages();
		if ($_REQUEST['d'] == 'log') echo __METHOD__ . '#' . __LINE__ . BR;
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	/**
	 * @param bool $createNew
	 * @return Index|IndexBE
	 */
	static function getInstance($createNew = true)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$instance = &self::$instance ?: $GLOBALS['i'];    // to read IndexBE instance
		if (!$instance && $createNew) {
			if ($_REQUEST['d'] == 'log') echo __METHOD__ . "<br />\n";
			$static = get_called_class();
			$instance = new $static();
			//$instance->initController();	// scheisse: call it in index.php
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $instance;
	}

	/**
	 * Called by index.php explicitly,
	 * therefore processes exceptions.
	 *
	 * That's not true anymore, called in render().
	 * @throws Exception
	 */
	public function initController()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($_REQUEST['d'] == 'log') echo __METHOD__ . "<br />\n";
		$slug = $this->request->getControllerString();
		if ($slug) {
			$this->loadController($slug);
		} else {
			throw new Exception404($slug);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	/**
	 * Usually autoload is taking care of the loading, but sometimes you want to check the path.
	 * Will call postInit() of the controller if available.
	 * @param $slug
	 * @throws Exception
	 */
	protected function loadController($slug)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$slugParts = explode('/', $slug);
		$class = end($slugParts);    // again, because __autoload need the full path
		//debug(__METHOD__, $slug, $class, class_exists($class));
		if (class_exists($class)) {
			$this->controller = new $class();
			//debug(get_class($this->controller));
			if (method_exists($this->controller, 'postInit')) {
				$this->controller->postInit();
			}
		} else {
			//debug($_SESSION['autoloadCache']);
			$exception = 'Class ' . $class . ' not found. Dev hint: try clearing autoload cache?';
			unset($_SESSION['AutoLoad']);
			if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
			throw new Exception($exception);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function render()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$content = '';
		try {
			$this->initController();
			if ($this->controller) {
				$content .= $this->renderController();
			} else {
				$content .= is_array($this->content)
					? implode("\n", $this->content)
					: $this->content;    // display Exception
				//$content .= $this->renderException(new Exception('Controller not found'));
			}
		} catch (LoginException $e) {
			//$this->content .= $e;
			throw $e;
		} catch (Exception $e) {
			$content = $this->renderException($e);
		}

		$content = $this->renderTemplateIfNotAjax($content);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		$content .= $this->renderProfiler();
		return $content;
	}

	function renderTemplateIfNotAjax($content)
	{
		if (!$this->request->isAjax() && !$this->request->isCLI()) {
			$contentOut = is_array($this->content)
				? implode("\n", $this->content)
				: $this->content;    // display Exception
			$contentOut .= $content;
			$contentOut = $this->renderTemplate($contentOut);
		} else {
			$contentOut = $content . $this->content;
			$this->content = '';        // clear for the next output. May affect saveMessages()
		}
		return $contentOut;
	}

	function renderTemplate($content)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$v = new View($this->template, $this);
		$v->content = $content;
		$v->title = strip_tags($this->controller->title);
		$v->sidebar = $this->showSidebar();
		$v->baseHref = $this->request->getLocation();
		//$lf = new LoginForm('inlineForm');	// too specific - in subclass
		//$v->loginForm = $lf->dispatchAjax();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $v;
	}

	function renderController()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$render = $this->controller->render();
		$render = $this->mergeStringArrayRecursive($render);
		if ($this->controller->layout instanceof Wrap && !$this->request->isAjax()) {
			$render = $this->controller->layout->wrap($render);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $render;
	}

	static function mergeStringArrayRecursive($render)
	{
		if (is_array($render)) {
			//$render = implode("\n", $render); // not recursive
			$combined = '';
			array_walk_recursive($render, function ($value, $key) use (&$combined) {
				$combined .= $value . "\n";
			});
			$render = $combined;
		}
		return $render;
	}

	function renderException(Exception $e, $wrapClass = '')
	{
		$content = '<div class="' . $wrapClass . ' ui-state-error alert alert-error alert-danger padding">
			' . get_class($e) . BR .
			$e->getMessage();
		if (DEVELOPMENT) {
			$content .= '<br />' . nl2br($e->getTraceAsString());
			//$content .= getDebug($e);
		}
		$content .= '</div>';
		$content .= '<div class="headerMargin"></div>';
		if ($e instanceof LoginException) {
			// catch this exception in your app Index class, it can't know what to do with all different apps
			//$lf = new LoginForm();
			//$content .= $lf;
		} elseif ($e instanceof Exception404) {
			$e->sendHeader();
		}

		return $content;
	}

	function __destruct()
	{
		if (is_object($this->user) && method_exists($this->user, '__destruct')) {
			$this->user->__destruct();
		}
	}

	function log($action, $bookingID)
	{
		$this->db->runInsertQuery('log', array(
			'who' => $this->user->id,
			'action' => $action,
			'booking' => $bookingID,
		));
	}

	function message($text)
	{
		$msg = '<div class="message alert alert-info ui-state-message alert alert-notice padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
	}

	function error($text)
	{
		$msg = '<div class="error ui-state-error alert alert-error alert-danger padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
	}

	function saveMessages()
	{
		$_SESSION[__CLASS__]['messages'] = $this->content;
	}

	function restoreMessages()
	{
		$this->content .= $_SESSION[__CLASS__]['messages'];
		$_SESSION[__CLASS__]['messages'] = '';
	}

	function addJQuery()
	{
		if (DEVELOPMENT || !$this->loadJSfromGoogle) {
			$jQueryPath = 'components/jquery/jquery.min.js';
			if (file_exists(AutoLoad::getInstance()->appRoot . $jQueryPath)) {
				$this->addJS($jQueryPath);
			} else {
				$this->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . $jQueryPath);
			}
		} else {
			$this->footer['jquery.js'] = '
				<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
				<script>window.jQuery || document.write(\'<script src="components/jquery/jquery.min.js"><\/script>\')</script>
			';
		}
		return $this;
	}

	function addJQueryUI()
	{
		$this->addJQuery();
		if (DEVELOPMENT || !$this->loadJSfromGoogle) {
			$jQueryPath = 'components/jquery-ui/ui/minified/jquery-ui.min.js';
			if (file_exists(AutoLoad::getInstance()->appRoot . $jQueryPath)) {
				$this->addJS($jQueryPath);
			} else {
				$this->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . $jQueryPath);
			}

			// commented out because this should be project specific
			//$this->addCSS('components/jquery-ui/themes/ui-lightness/jquery-ui.min.css');
		} else {
			$this->footer['jqueryui.js'] = '<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
			<script>window.jQueryUI || document.write(\'<script src="components/jquery-ui/ui/minified/jquery-ui.min.js"><\/script>\')</script>';
			$this->addCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/ui-lightness/jquery-ui.css');
		}
		return $this;
	}

	/**
	 * @param $source
	 * @return Index
	 */
	function addJS($source)
	{
		$this->footer[$source] = '<script src="' . $source . '"></script>';
		return $this;
	}

	/**
	 * @param $source
	 * @return Index
	 */
	function addCSS($source)
	{
		if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) == 'less') {
			$source = '?c=Lesser&css=' . $source;
		}
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="' . $source . '" />';
		return $this;
	}

	function showSidebar()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if (method_exists($this->controller, 'sidebar')) {
			$content = $this->controller->sidebar();
			$content = $this->mergeStringArrayRecursive($content);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	function renderProfiler()
	{
		if (DEVELOPMENT &&
			isset($GLOBALS['profiler']) &&
			!$this->request->isAjax() &&
			//!$this->request->isCLI() &&
			!in_array(get_class($this->controller), array('Lesser'))) {
			$profiler = $GLOBALS['profiler'];
			/** @var $profiler TaylorProfiler */
			if ($profiler) {
				if (!$this->request->isCLI()) {
					$content = $profiler->renderFloat();
					$content .= '<div class="profiler">' . $profiler->printTimers(true) . '</div>';
					//$content .= '<div class="profiler">'.$profiler->printTrace(true).'</div>';
					//$content .= '<div class="profiler">'.$profiler->analyzeTraceForLeak().'</div>';
					if ($this->db->queryLog) {
						$content .= '<div class="profiler">' . TaylorProfiler::dumpQueries() . '</div>';
					}
					if ($this->db->QUERIES) {
						$dbLayer = $this->db;
						/** @var $dbLayer dbLayer */
						$content .= $dbLayer->dumpQueries();
					}
				}
			} else if (DEVELOPMENT && !$this->request->isCLI()) {
				$content = TaylorProfiler::renderFloat();
			}
		}
		return $content;
	}

	function implodeCSS()
	{
		//return implode("\n", $this->header);
		$content = array();
		foreach ($this->header as $key => $script) {
			$content[] = '<!--' . $key . '-->' . $script;
		}
		return implode("\n", $content);
	}

	function implodeJS()
	{
		return implode("\n", $this->footer);
	}

}
