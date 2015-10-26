<?php

class IndexBase /*extends Controller*/ {	// infinite loop

	/**
	 * @var MySQL
	 */
	protected $db;

	/**
	 * @see Config for a public property
	 * @var LocalLangDummy
	 */
	protected $ll;

	/**
	 * @var User|LoginUser
	 */
	protected $user;

	/**
	 * For any error messages during initialization.
	 *
	 * @var string|array|\nadlib\HTML\Messages
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

	public $sidebar = '';

	public $appName = 'Project name';

	public $description = '';

	public $keywords = '';

	public $bodyClasses = array();

	/**
	 * @var Config
	 */
	var $config;

	public function __construct() {
		TaylorProfiler::start(__METHOD__);
		//parent::__construct();
		if (class_exists('Config')) {
			$this->config = Config::getInstance();
			$this->db = $this->config->db;
			$this->user = $this->config->user;
			$this->ll = $this->config->getLL();
		}

		$this->request = Request::getInstance();
		//debug('session_start');

		// only use session if not run from command line
		if (!Request::isCLI() && !session_id() /*&& session_status() == PHP_SESSION_NONE*/ && !headers_sent()) {
			session_start();
		}

		$this->content = new nadlib\HTML\Messages();
		$this->content->restoreMessages();
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * @param bool $createNew - must be false
	 * @return Index|IndexBE
	 */
	static function getInstance($createNew = false) {
		TaylorProfiler::start(__METHOD__);
		$instance = self::$instance
			? self::$instance
			: (isset($GLOBALS['i']) ? $GLOBALS['i'] : NULL);	// to read IndexBE instance
		if (!$instance && $createNew) {
			$static = get_called_class();
			$instance = new $static();
			self::$instance = $instance;
		}
		TaylorProfiler::stop(__METHOD__);
		return $instance;
	}

	/**
	 * Called by index.php explicitly,
	 * therefore processes exceptions.
	 *
	 * That's not true anymore, called in render().
	 * @throws Exception
	 */
	public function initController() {
		TaylorProfiler::start(__METHOD__);
		$slug = $this->request->getControllerString();
		if ($slug) {
			$this->loadController($slug);
			$this->bodyClasses[] = get_class($this->controller);
		} else {
			throw new Exception404($slug);
		}
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * Usually autoload is taking care of the loading, but sometimes you want to check the path.
	 * Will call postInit() of the controller if available.
	 * @param $slug
	 * @throws Exception
	 */
	protected function loadController($slug) {
		TaylorProfiler::start(__METHOD__);
		$slugParts = explode('/', $slug);
		$class = end($slugParts);	// again, because __autoload need the full path
		//debug(__METHOD__, $slug, $class, class_exists($class));
		if (class_exists($class)) {
			$this->controller = new $class();
			//debug(get_class($this->controller));
			if (method_exists($this->controller, 'postInit')) {
				$this->controller->postInit();
			}
		} else {
			//debug($_SESSION['autoloadCache']);
			$exception = 'Class '.$class.' not found. Dev hint: try clearing autoload cache?';
			unset($_SESSION['AutoLoad']);
			TaylorProfiler::stop(__METHOD__);
			throw new Exception404($exception);
		}
		TaylorProfiler::stop(__METHOD__);
	}

	function getController() {
		if (!$this->controller) {
			$this->initController();
		}
		return $this->controller;
	}

	function render() {
		TaylorProfiler::start(__METHOD__);
		$content = '';
		try {
			$this->initController();
			if ($this->controller) {
				$content .= $this->renderController();
			} else {
				$content .= $this->content;	// display Exception
				//$content .= $this->renderException(new Exception('Controller not found'));
			}
		} catch (LoginException $e) {
			//$this->content .= $e;
			throw $e;
		} catch (Exception $e) {
			$content = $this->renderException($e);
		}

		$content = $this->renderTemplateIfNotAjax($content);
		TaylorProfiler::stop(__METHOD__);
		$content .= $this->renderProfiler();
		return $content;
	}

	function renderTemplateIfNotAjax($content) {
		$contentOut = '';
		if (!$this->request->isAjax() && !$this->request->isCLI()) {
			$contentOut .= $this->content;	// display Exception
			$contentOut .= $content;
			$contentOut = $this->renderTemplate($contentOut)->render();
		} else {
			//$contentOut .= $this->content;    // NO! it's JSON (maybe)
			$contentOut .= $content;
			$this->content->clear();		// clear for the next output. May affect saveMessages()
		}
		return $contentOut;
	}

	function renderTemplate($content) {
		TaylorProfiler::start(__METHOD__);
		$v = new View($this->template, $this);
		$v->content = $content;
		$v->title = strip_tags(ifsetor($this->controller->title));
		$v->sidebar = $this->sidebar;
		$v->baseHref = $this->request->getLocation();
		//$lf = new LoginForm('inlineForm');	// too specific - in subclass
		//$v->loginForm = $lf->dispatchAjax();
		TaylorProfiler::stop(__METHOD__);
		return $v;
	}

	function renderController() {
		TaylorProfiler::start(__METHOD__);
		$render = $this->controller->render();
		$render = $this->mergeStringArrayRecursive($render);
		$this->sidebar = $this->showSidebar();
		if ($this->controller->layout instanceof Wrap
			&& !$this->request->isAjax()) {
			/** @var $this->controller->layout Wrap */
			$render = $this->controller->layout->wrap($render);
			$render = str_replace('###SIDEBAR###', $this->showSidebar(), $render);
		}
		TaylorProfiler::stop(__METHOD__);
		return $render;
	}

	function s($content) {
		return $this->mergeStringArrayRecursive($content);
	}

	/**
	 * @param string|string[] $render
	 * @return string
	 */
	static function mergeStringArrayRecursive($render) {
		TaylorProfiler::start(__METHOD__);
		if (is_array($render)) {
			$combined = '';
			/*array_walk_recursive($render,
				array('IndexBase', 'walkMerge'),
				$combined); // must have &
			*/

			//$combined = array_merge_recursive($render);
			//$combined = implode('', $combined);

			$combinedA = new ArrayObject();
			array_walk_recursive($render, array('IndexBase', 'walkMergeArray'), $combinedA);
			$combined = implode('', $combinedA->getArrayCopy());
			$render = $combined;
		} else if (is_object($render)) {
			//debug(get_class($render));
			$render = $render.'';
		}
		TaylorProfiler::stop(__METHOD__);
		return $render;
	}

	protected static function walkMerge($value, $key, &$combined = '') {
		$combined .= $value."\n";
	}

	protected static function walkMergeArray($value, $key, $combined) {
		$combined[] = $value;
	}

	function renderException(Exception $e, $wrapClass = '') {
		if ($this->controller) {
			$this->controller->title = $e->getMessage();
		}
		$message = $e->getMessage();
		$message = ($message instanceof htmlString ||
			$message[0] == '<')
			? $message.''
			: htmlspecialchars($message);
		$content = '<div class="'.$wrapClass.' ui-state-error alert alert-error alert-danger padding">
			'.get_class($e).' ('.$e->getCode().')'.BR.
			nl2br($message);
		if (DEVELOPMENT) {
			$content .= BR.BR.'<div style="text-align: left">'.
				nl2br($e->getTraceAsString()).'</div>';
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

	function __destruct() {
		if (is_object($this->user) && method_exists($this->user, '__destruct')) {
			$this->user->__destruct();
		}
	}

	/**
	 * Move it to the MRBS
	 * @param string $action
	 * @param array $data
	 */
	function log($action, array $data) {
		//debug($action, $bookingID);
		/*$this->db->runInsertQuery('log', array(
			'who' => $this->user->id,
			'action' => $action,
			'booking' => $bookingID,
		));*/
	}

	function message($text) {
		$this->content->message($text);
	}

	function error($text) {
		$this->content->error($text);
	}

	function success($text) {
		$this->content->success($text);
	}

	function info($text) {
		$this->content->info($text);
	}

	/**
	 * @return $this
	 */
	function addJQuery() {
		if (isset($this->footer['jquery.js'])) {
			return $this;
		}
		if (DEVELOPMENT || !$this->loadJSfromGoogle) {
			$jQueryPath = 'jquery/jquery.min.js';
			$al = AutoLoad::getInstance();
			nodebug(array(
				'jQueryPath' => $jQueryPath,
				'appRoot' => $al->appRoot,
				'componentsPath' => $al->componentsPath,
				'fe(jQueryPath)' => file_exists($jQueryPath),
				'fe(appRoot)' => file_exists($al->appRoot . $jQueryPath),
				'fe(nadlibFromDocRoot)' => file_exists($al->nadlibFromDocRoot . $jQueryPath),
				'fe(componentsPath)' => file_exists($al->componentsPath . $jQueryPath),
				'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
				'documentRoot' => $al->documentRoot,
				'componentsPath.jQueryPath' => $al->componentsPath.$jQueryPath,
			));
			if (file_exists($al->componentsPath . $jQueryPath)) {
				//debug(__LINE__, $al->componentsPath, $al->componentsPath->getURL());
				$this->addJS($al->componentsPath->getURL().$jQueryPath);
				return $this;
			} elseif (file_exists($al->appRoot . $jQueryPath)) {
                // does not work if both paths are the same!!
//				$rel = Path::make(getcwd())->remove($al->appRoot);
                $rel = Path::make(Config::getInstance()->documentRoot)->remove($al->appRoot);
				$rel->trimIf('be');
				$rel->reverse();
				$this->addJS($rel . $jQueryPath);
				return $this;
			} elseif (file_exists($al->nadlibFromDocRoot . $jQueryPath)) {
				$this->addJS($al->nadlibFromDocRoot . $jQueryPath);
				return $this;
			} else {
				$jQueryPath = 'components/jquery/jquery.min.js';
			}
		} else {
			$jQueryPath = 'components/jquery/jquery.min.js';
		}
		$this->footer['jquery.js'] = '
			<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
			<script>window.jQuery || document.write(\'<script src="'.$jQueryPath.'"><\/script>\')</script>
			';
		return $this;
	}

	function addJQueryUI() {
		$this->addJQuery();
		if (ifsetor($this->footer['jqueryui.js'])) return $this;
		$al = AutoLoad::getInstance();
		$jQueryPath = clone $al->componentsPath;
		//debug($jQueryPath);
		//$jQueryPath->appendString('jquery-ui/ui/minified/jquery-ui.min.js');
		$jQueryPath->appendString('jquery-ui/jquery-ui.min.js');
		$jQueryPath->setAsFile();
		nodebug(array(
			'jQueryPath' => $jQueryPath,
			'jQueryPath->exists()' => $jQueryPath->exists(),
			'appRoot' => $al->appRoot,
			'componentsPath' => $al->componentsPath,
			'fe(jQueryPath)' => file_exists($jQueryPath->getUncapped()),
			'fe(appRoot)' => file_exists($al->appRoot . $jQueryPath->getUncapped()),
			'fe(nadlibFromDocRoot)' => file_exists($al->nadlibFromDocRoot . $jQueryPath),
			'fe(componentsPath)' => file_exists($al->componentsPath . $jQueryPath),
			'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
			'documentRoot' => $al->documentRoot,
			'componentsPath.jQueryPath' => $al->componentsPath.$jQueryPath,
		));
		if (DEVELOPMENT || !$this->loadJSfromGoogle) {
			if ($jQueryPath->exists()) {
				$this->addJS($jQueryPath->relativeFromAppRoot()->getUncapped());
				return $this;
			} else {
				$jQueryPath = clone $al->componentsPath;
				$jQueryPath->appendString('jquery-ui/jquery-ui.js');
				$jQueryPath->setAsFile();
				if ($jQueryPath->exists()) {
					$this->addJS($jQueryPath->relativeFromAppRoot()->getUncapped());
					return $this;
				}
			}
		}

		// commented out because this should be project specific
		//$this->addCSS('components/jquery-ui/themes/ui-lightness/jquery-ui.min.css');
		$this->footer['jqueryui.js'] = '<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
		<script>window.jQueryUI || document.write(\'<script src="'.$jQueryPath.'"><\/script>\')</script>';
		$this->addCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/ui-lightness/jquery-ui.css');
		return $this;
	}

	/**
	 * @param $source
	 * @param bool $defer
	 * @return Index
	 */
	function addJS($source, $defer = true) {
		if (class_exists('Debug')) {
			$called = Debug::getCaller();
		} else {
			$called = '';
		}
		$fileName = $source;
		if (!contains($source, '//') && !contains($source, '?')) {	// don't download URL
			$mtime = @filemtime($source);
			if (!$mtime) {
				$mtime = @filemtime('public/'.$source);
			}
			if ($mtime) {
				$fileName .= '?' . $mtime;
			}
		}
		$defer = $defer ? 'defer="true"' : '';
		$this->footer[$source] = '<!-- '.$called.' --><script src="'.$fileName.'" '.$defer.'></script>';
		return $this;
	}

	/**
	 * @param $source
	 * @return Index
	 */
	function addCSS($source) {
		if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) == 'less') {
			if ($this->request->apacheModuleRewrite() && file_exists('css/.htaccess')) {
				//$source = $source;	// rewrite inside css folder
			} else {
				$sourceCSS = str_replace('.less', '.css', $source);
				if (file_exists($sourceCSS)){
					$source = $sourceCSS;
				} else {
					$source = 'css/?c=Lesser&css=' . $source;
				}
			}
		} else {
			if (!contains($source, '//') && !contains($source, '?')) {	// don't download URL
				$mtime = @filemtime($source);
				if (!$mtime) {
					$mtime = @filemtime('public/'.$source);
				}
				if ($mtime) {
					$source .= '?' . $mtime;
				}
			}
		}
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="'.$source.'" />';
		return $this;
	}

	function showSidebar() {
		TaylorProfiler::start(__METHOD__);
		$content = '';
		if (method_exists($this->controller, 'sidebar')) {
			$content = $this->controller->sidebar();
			$content = $this->mergeStringArrayRecursive($content);
		}
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	function renderProfiler() {
		$content = '';
		if (DEVELOPMENT &&
			isset($GLOBALS['profiler']) &&
			!$this->request->isAjax() &&
			//!$this->request->isCLI() &&
			!in_array(get_class($this->controller), array('Lesser')))
		{
			$profiler = $GLOBALS['profiler'];
			/** @var $profiler TaylorProfiler */
			if ($profiler) {
				if (!$this->request->isCLI()) {
					$content = $profiler->renderFloat();
					$content .= '<div class="profiler noprint">'.$profiler->printTimers(true).'</div>';
					//$content .= '<div class="profiler">'.$profiler->printTrace(true).'</div>';
					//$content .= '<div class="profiler">'.$profiler->analyzeTraceForLeak().'</div>';
					if (ifsetor($this->db->queryLog)) {
						$content .= '<div class="profiler">'.TaylorProfiler::dumpQueries().'</div>';
					}
					$content .= TaylorProfiler::dumpQueries();
				}
			} else if (DEVELOPMENT && !$this->request->isCLI()) {
				$content = TaylorProfiler::renderFloat();
			}
		}
		return $content;
	}

	function implodeCSS() {
		$content = array();
		foreach ($this->header as $key => $script) {
			$content[] = '<!--'.$key.'-->'."\n".$script;
		}
		return implode("\n", $content);
	}

	function implodeJS() {
		if (file_exists('vendor/minify/min/index.php')) {
			$include = array(); // some files can't be found
			$files = array_keys($this->footer);
			foreach ($files as $f => &$file) {
				if (file_exists($file)) {
					$file = $this->request->getDocumentRoot() . $file;
				} else {
					unset($files[$f]);
					$include[$file] = $this->footer[$file];
				}
			}
			$files = implode(",", $files);
			//$files .= DEVELOPMENT ? '&debug' : '';
			$content = '<script src="vendor/minify/min/?f='.$files.'"></script>';
			$content .= implode("\n", $include);
		} else {
			$content = implode("\n", $this->footer);
		}
		return $content;
	}

	function addBodyClass($name) {
		$this->bodyClasses[$name] = $name;
	}

}
