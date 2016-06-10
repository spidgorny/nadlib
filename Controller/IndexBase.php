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
	 * @public for template.phtml
	 */
	public $user;

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

	var $csp = array(
		"default-src" => array(
			"'self'",
			"'unsafe-inline'",
			'http://maps.google.com/',
			'http://csi.gstatic.com/',
			'http://maps.googleapis.com',
			'http://fonts.googleapis.com/',
			'http://maps.gstatic.com/',
			'http://fonts.gstatic.com/',
			'http://mt0.googleapis.com/',
			'http://mt1.googleapis.com/',
			'http://maxcdn.bootstrapcdn.com/',
			'http://ajax.googleapis.com/',
		),
		"img-src" => array(
			"'self'",
			'http://maps.google.com/',
			'http://csi.gstatic.com/',
			'http://maps.googleapis.com',
			'http://fonts.googleapis.com/',
			'http://maps.gstatic.com/',
			'http://mt0.googleapis.com/',
			'http://mt1.googleapis.com/',
			'http://whc.unesco.org/',
			'data:',
		),
		"connect-src" => array(
			"'self'",
		),
	);

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

		$this->content = new nadlib\HTML\Messages();
		$this->content->restoreMessages();

		$this->setSecurityHeaders();
		TaylorProfiler::stop(__METHOD__);
	}

	function initSession() {
		if (!Request::isCLI() && !session_id() /*&& session_status() == PHP_SESSION_NONE*/ && !headers_sent()) {
			ini_set('session.use_trans_sid', false);
			ini_set('session.use_only_cookies', true);
			ini_set('session.cookie_httponly', true);
			ini_set('session.hash_bits_per_character', 6);
			ini_set('session.hash_function', 'sha512');
			session_start();
		}
		if (ifsetor($_SESSION['HTTP_USER_AGENT'])) {
			if ($_SESSION['HTTP_USER_AGENT'] != $_SERVER['HTTP_USER_AGENT']) {
				session_regenerate_id(true);
				unset($_SESSION['HTTP_USER_AGENT']);
				throw new AccessDeniedException('Session hijacking detected. Please try again');
			}
		} else {
			$_SESSION['HTTP_USER_AGENT'] = ifsetor($_SERVER['HTTP_USER_AGENT']);
		}
		if (ifsetor($_SESSION['REMOTE_ADDR'])) {
			if ($_SESSION['REMOTE_ADDR'] != $_SERVER['REMOTE_ADDR']) {
				session_regenerate_id(true);
				unset($_SESSION['REMOTE_ADDR']);
				throw new AccessDeniedException('Session hijacking detected. Please try again.');
			}
		} else {
			$_SESSION['REMOTE_ADDR'] = ifsetor($_SERVER['REMOTE_ADDR']);
		}
//		debug($_SESSION['HTTP_USER_AGENT'], $_SESSION['REMOTE_ADDR']);
//		debug($_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
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
		if (!$this->controller) {
			$slug = $this->request->getControllerString();
			if ($slug) {
				$this->loadController($slug);
				$this->bodyClasses[] = get_class($this->controller);
			} else {
				throw new Exception404($slug);
			}
		}
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * Usually autoload is taking care of the loading, but sometimes you want to check the path.
	 * Will call postInit() of the controller if available.
	 * @param $class
	 * @throws Exception
	 */
	protected function loadController($class) {
		TaylorProfiler::start(__METHOD__);
		$slugParts = explode('/', $class);
		$class = end($slugParts);	// again, because __autoload need the full path
		//debug(__METHOD__, $slug, $class, class_exists($class));
		if (class_exists($class)) {
			$this->controller = new $class();
//			debug($class, get_class($this->controller));
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
			// only use session if not run from command line
			$this->initSession();

			$this->initController();
			if ($this->controller) {
				$content .= $this->renderController();
			} else {
				// display Exception
				$content .= $this->content->getContent();
				//$content .= $this->renderException(new Exception('Controller not found'));
			}
		} catch (LoginException $e) {
			$this->content[] = $e->getMessage();
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
			// display Exception
			$contentOut .= $this->content->getContent();
			$contentOut .= $this->s($content);
			$view = $this->renderTemplate($contentOut);
			//echo gettype2($view), BR;
			if ($view instanceof View) {
				$contentOut = $view->render();
			} else {
				$contentOut = $view;
			}
		} else {
			//$contentOut .= $this->content;    // NO! it's JSON (maybe)
			$contentOut .= $this->s($content);
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
		$render = $this->s($render);
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
		return MergedContent::mergeStringArrayRecursive($content);
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
			// called automatically(!)
			//$this->user->__destruct();
		}
	}

	/**
	 * Move it to the MRBS
	 * @param string $action
	 * @param mixed $data
	 */
	function log($action, $data) {
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
		if ($this->loadJSfromGoogle) {
			$jQueryPath = 'components/jquery/jquery.min.js';
			$this->footer['jquery.js'] = '
			<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
			<script>window.jQuery || document.write(\'<script src="' . $jQueryPath . '"><\/script>\')</script>
			';
		} else {
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
			$this->addJS($jQueryPath);
		}
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
			$fn = new Path($fileName);
			$fileName = $fn->relativeFromAppRoot();
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
					$source = $this->addMtime($source);
				} else {
					$source = 'css/?c=Lesser&css=' . $source;
				}
			}
		} else {
			$source = $this->addMtime($source);
		}
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="'.$source.'" />';
		return $this;
	}

	function addMtime($source) {
		if (!contains($source, '//') && !contains($source, '?')) {	// don't download URL
			$mtime = @filemtime($source);
			if (!$mtime) {
				$mtime = @filemtime('public/'.$source);
			}
			if ($mtime) {
				$source .= '?' . $mtime;
			}
		}
		return $source;
	}

	function showSidebar() {
		TaylorProfiler::start(__METHOD__);
		$content = '';
		if (method_exists($this->controller, 'sidebar')) {
			$content = $this->controller->sidebar();
			$content = $this->s($content);
		}
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	function renderProfiler() {
		$content = '';
		if (DEVELOPMENT &&
			!$this->request->isAjax() &&
			//!$this->request->isCLI() &&
			!in_array(get_class($this->controller), array('Lesser')))
		{
			if (!$this->request->isCLI()) {
				$ft = new FloatTime(true);
				$content .= $ft->render();
				$content .= '<div class="profiler noprint">';
				$content .= $this->s(OODBase::getCacheStatsTable());

				/** @var $profiler TaylorProfiler */
				$profiler = TaylorProfiler::getInstance();
				if ($profiler) {
					$content .= $profiler->printTimers(true);
					$content .= TaylorProfiler::dumpQueries();
					//$content .= $profiler->printTrace(true);
					//$content .= $profiler->analyzeTraceForLeak();
				}

				$content .= '</div>';
			}
		}
		return $content;
	}

	function implodeCSS() {
		$content = array();
		foreach ($this->header as $key => $script) {
			$content[] = '<!--'.$key.'-->'."\n".$script;
		}
		return implode("\n", $content)."\n";
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
			$content = implode("\n", $this->footer)."\n";
		}
		return $content;
	}

	function addBodyClass($name) {
		$this->bodyClasses[$name] = $name;
	}

	/**
	 * @return string
	 */
	public function setSecurityHeaders() {
		if (!headers_sent()) {
			header('X-Frame-Options: SAMEORIGIN');
			header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
			foreach ($this->csp as $key => &$val) {
				$val = $key . ' ' . implode(' ', $val);
			}
			header('Content-Security-Policy: ' . implode('; ', $this->csp));
			header('X-Content-Security-Policy: ' . implode('; ', $this->csp));
		}
	}

}