<?php

use nadlib\HTTP\Session;

class IndexBase /*extends Controller*/
{    // infinite loop

	/**
	 * @var MySQL
	 */
	protected $db;

	/**
	 * @see Config for a public property
	 * @var LocalLangDummy
	 */
	public $ll;

	/**
	 * @var User|LoginUser
	 * @public for template.phtml
	 */
	protected $user;

	/**
	 * For any error messages during initialization.
	 *
	 * @var \nadlib\HTML\Messages
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

	public $header = [];

	public $footer = [];

	public $loadJSfromGoogle = true;

	public $template = 'template.phtml';

	public $sidebar = '';

	public $appName = 'Project name';

	public $description = '';

	public $keywords = '';

	public $bodyClasses = [];

	/**
	 * @var Config
	 */
	protected $config;

	var $csp = [
		"default-src" => [
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
		],
		"img-src" => [
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
		],
		"connect-src" => [
			"'self'",
		],
		"script-src" => [
			"'self'",
			"'unsafe-inline'",
			"'unsafe-eval'",
		],
	];

	/**
	 * @var Request
	 */
	protected $request;

	public function __construct(ConfigInterface $config)
	{
		TaylorProfiler::start(__METHOD__);
		//parent::__construct();
		$this->config = $config;
		$this->db = $this->config->getDB();

		// copy/paste this to class Index if your project requires login
		// you need a session if you want to try2login()
//		$this->initSession();
//		$this->user = $this->config->getUser();

		$this->ll = $this->config->getLL();

		$this->request = $this->config->getRequest();
		//debug('session_start');

		$this->content = new nadlib\HTML\Messages();
		$this->content->restoreMessages();

		$this->setSecurityHeaders();

//		$this->controller = (object)[
//			'layout' => null,
//		];
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * @throws AccessDeniedException
	 */
	public function initSession()
	{
//		debug('is session started', session_id(), session_status());
		if (!Request::isCLI() && !Session::isActive() && !headers_sent()) {
			ini_set('session.use_trans_sid', false);
			ini_set('session.use_only_cookies', true);
			ini_set('session.cookie_httponly', true);
			ini_set('session.hash_bits_per_character', 6);
			ini_set('session.hash_function', 'sha512');
			$ok = session_start();
			if (!$ok) {
				throw new RuntimeException('session_start() failed');
			} else {
				//debug('session_start', session_id());
			}
		} else {
//			debug('session already started', session_id(), session_status());
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
	 * @param ConfigInterface|null $config
	 * @return Index|IndexBE
	 * @throws Exception
	 */
	public static function getInstance($createNew = false, ConfigInterface $config = null)
	{
		TaylorProfiler::start(__METHOD__);
		$instance = self::$instance
			? self::$instance
			: null;
		if (!$instance && $createNew) {
			$static = get_called_class();
			$instance = new $static($config);
			self::$instance = $instance;
		}
		TaylorProfiler::stop(__METHOD__);
		return $instance;
	}

	/**
	 * TODO: Remove the boolean parameter from getInstance()
	 * TODO: And force to use makeInstance() in case it was true
	 * @param Config|null $config
	 * @return Index|IndexBE
	 * @throws Exception
	 */
	public static function makeInstance(Config $config = null)
	{
		return static::getInstance(true, $config);
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
		// already created
		if ($this->controller instanceof Controller) {
			return;
		}
		$slug = $this->request->getControllerString();
//		llog($slug);
		if (!$slug) {
			throw new Exception404($slug);
		}
		if ($_REQUEST['d']) {
			$this->log(__METHOD__, $slug);
		}
		$this->loadController($slug);
		$this->bodyClasses[] = is_object($this->controller) ? get_class($this->controller) : '';
	}

	/**
	 * Usually autoload is taking care of the loading, but sometimes you want to check the path.
	 * Will call postInit() of the controller if available.
	 * @param string $class
	 * @throws Exception
	 */
	protected function loadController($class)
	{
		TaylorProfiler::start(__METHOD__);
		$slugParts = explode('/', $class);
		$class = end($slugParts);    // again, because __autoload needs the full path
//		debug(__METHOD__, $slugParts, $class, class_exists($class));
		if (class_exists($class)) {
			$this->controller = $this->makeController($class);
		} else {
			//debug($_SESSION['autoloadCache']);
			$exception = 'Class ' . $class . ' not found. Dev hint: try clearing autoload cache?';
			unset($_SESSION['AutoLoad']);
			TaylorProfiler::stop(__METHOD__);
			throw new Exception404($exception);
		}
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * @param string $class
	 * @return AppController
	 * @throws ReflectionException
	 */
	public function makeController($class)
	{
//		llog($class);
		if (method_exists($this->config, 'getDI')) {
			$di = $this->config->getDI();
			$this->controller = $di->get($class);
		} else {
			// v2
			$ms = new MarshalParams($this->config);
			$this->controller = $ms->make($class);
		}

		// debug($class, get_class($this->controller));
		if (method_exists($this->controller, 'postInit')) {
			$this->controller->postInit();
		}
		return $this->controller;
	}

	/**
	 * @return AppController
	 * @throws Exception
	 */
	public function getController()
	{
		if (!$this->controller) {
			$this->initController();
		}
		//debug(get_class($this->controller));
		return $this->controller;
	}

	public function render()
	{
		TaylorProfiler::start(__METHOD__);
		$content = '';
		try {
			// only use session if not run from command line
			$this->initSession();

			$this->initController();
			if ($this->controller instanceof Controller) {
				$content .= $this->renderController();
			} else {
				// display Exception
				$content .= $this->content->getContent();
				$this->content->clear();
				//$content .= $this->renderException(new Exception('Controller not found'));
			}
		} catch (Exception $e) {    // handles ALL exceptions
			$content = $this->renderException($e);
		}

		$content = $this->renderTemplateIfNotAjax($content);
		TaylorProfiler::stop(__METHOD__);
		$content .= $this->renderProfiler();
		return $content;
	}

	public function renderController()
	{
		TaylorProfiler::start(__METHOD__);
		$notOptions = array_filter(
			array_slice(
				ifsetor($_SERVER['argv'], []),
				1
			),
			function ($el) {
				return $el[0] != '-';    // --options
			}
		);
//		debug($notOptions); exit;
		// $notOptions[0] is the controller
		$method = ifsetor($notOptions[1], 'render');
		if ($method && method_exists($this->controller, $method)) {
			//echo 'Method: ', $method, BR;
			//$params = array_slice($_SERVER['argv'], 3);
			//debug($this->request->getAll());
			$marshal = new MarshalParams($this->controller);
			$render = $marshal->call($method);
			//$render = $this->controller->$method();
		} else {
			$render = $this->renderException(
				new InvalidArgumentException('Method ' . $method . ' is not callable on ' . get_class($this->controller))
			);
		}
		$render = $this->s($render);
		$this->sidebar = $this->showSidebar();
		if ($this->controller->layout instanceof Wrap
			&& !$this->request->isAjax()) {
			/** @var $this ->controller->layout Wrap */
			$render = $this->controller->layout->wrap($render);
			$render = str_replace('###SIDEBAR###', $this->showSidebar(), $render);
		}
		TaylorProfiler::stop(__METHOD__);
		return $render;
	}

	public function renderTemplateIfNotAjax($content)
	{
		$contentOut = '';
		if (!$this->request->isAjax() && !$this->request->isCLI()) {
			// display Exception
			$view = $this->renderTemplate($content);
			//echo gettype2($view), BR;
			if ($view instanceof View) {
				$contentOut = $view->render();
			} else {
				$contentOut = $view;
			}
		} else {
			//$contentOut .= $this->content;    // NO! it's JSON (maybe)
			$contentOut .= $this->s($content);
		}
		return $contentOut;
	}

	public function renderTemplate($content)
	{
		TaylorProfiler::start(__METHOD__);
		$contentOut = '';
		// this is already output
		$contentOut .= $this->content->getContent();
		// clear for the next output. May affect saveMessages()
//		$this->content->clear();
		$contentOut .= $this->s($content);

		$v = new View($this->template, $this);
		$v->content = $contentOut;
		$v->title = strip_tags(ifsetor($this->controller->title));
		$v->sidebar = $this->sidebar;
		$v->baseHref = $this->request->getLocation();
		//$lf = new LoginForm('inlineForm');	// too specific - in subclass
		//$v->loginForm = $lf->dispatchAjax();
		TaylorProfiler::stop(__METHOD__);
		return $v;
	}

	public function s($content)
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	/**
	 * Does not catch LoginException - show your login form in Index
	 * @param Exception $e
	 * @param string $wrapClass
	 * @return string
	 */
	public function renderException(Exception $e, $wrapClass = 'ui-state-error alert alert-error alert-danger padding flash flash-warn flash-error')
	{
		if ($this->request->isCLI()) {
			echo get_class($e),
			' #', $e->getCode(),
			': ', $e->getMessage(), BR;
			echo $e->getTraceAsString(), BR;
			return '';
		}

		http_response_code($e->getCode());
		if ($this->controller) {
			$this->controller->title = get_class($this->controller);
		}

		$message = $e->getMessage();
		$message = ($message instanceof htmlString ||
			$message[0] == '<')
			? $message . ''
			: htmlspecialchars($message);
		$content = '<div class="' . $wrapClass . '">
				' . get_class($e) .
			($e->getCode() ? ' (' . $e->getCode() . ')' : '') . BR .
			nl2br($message);
		if (DEVELOPMENT || 0) {
			$content .= BR . '<hr />' . '<div style="text-align: left">' .
				nl2br($e->getTraceAsString()) . '</div>';
			//$content .= getDebug($e);
		}
		$content .= '</div>';
		if ($e instanceof LoginException) {
			// catch this exception in your app Index class, it can't know what to do with all different apps
			//$lf = new LoginForm();
			//$content .= $lf;
		} elseif ($e instanceof Exception404) {
			$e->sendHeader();
		}

		return $content;
	}

	public function __destruct()
	{
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
	public function log($action, $data)
	{
		//debug($action, $bookingID);
		/*$this->db->runInsertQuery('log', array(
			'who' => $this->user->id,
			'action' => $action,
			'booking' => $bookingID,
		));*/
	}

	public function message($text)
	{
		return $this->content->message($text);
	}

	public function error($text)
	{
		return $this->content->error($text);
	}

	public function success($text)
	{
		return $this->content->success($text);
	}

	public function info($text)
	{
		return $this->content->info($text);
	}

	/**
	 * @param bool $defer
	 * @return $this
	 */
	public function addJQuery($defer = true)
	{
		if (isset($this->footer['jquery.js'])) {
			return $this;
		}
		if ($this->loadJSfromGoogle) {
			$jQueryPath = 'node_modules/jquery/dist/jquery.min.js';
			$this->footer['jquery.js'] = '
			<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
			<script>window.jQuery || document.write(\'<script src="' . $jQueryPath . '"><\/script>\')</script>
			';
		} else {
			$jQueryPath = 'jquery/jquery.min.js';
			$al = AutoLoad::getInstance();
			$appRoot = $al->getAppRoot();
			nodebug([
				'jQueryPath' => $jQueryPath,
				'appRoot' => $appRoot,
				'componentsPath' => $al->componentsPath,
				'fe(jQueryPath)' => file_exists($jQueryPath),
				'fe(appRoot)' => file_exists($appRoot . $jQueryPath),
				'fe(nadlibFromDocRoot)' => file_exists($al->nadlibFromDocRoot . $jQueryPath),
				'fe(componentsPath)' => file_exists($al->componentsPath . $jQueryPath),
				'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
				'documentRoot' => $al->documentRoot,
				'componentsPath.jQueryPath' => $al->componentsPath . $jQueryPath,
			]);
			if (file_exists($al->componentsPath . $jQueryPath)) {
				//debug(__LINE__, $al->componentsPath, $al->componentsPath->getURL());
				$this->addJS(cap($al->componentsPath->getURL()) . $jQueryPath, $defer);
				return $this;
			} elseif (file_exists($appRoot . $jQueryPath)) {
				// does not work if both paths are the same!!
//				$rel = Path::make(getcwd())->remove($al->appRoot);
				$rel = Path::make(Config::getInstance()->documentRoot)->remove($appRoot);
				$rel->trimIf('be');
				$rel->reverse();
				$this->addJS($rel . $jQueryPath, $defer);
				return $this;
			} elseif (file_exists($al->nadlibFromDocRoot . $jQueryPath)) {
				$this->addJS($al->nadlibFromDocRoot . $jQueryPath, $defer);
				return $this;
			} else {
				$jQueryPath = 'node_modules/jquery/dist/jquery.min.js';
			}
			$this->addJS($jQueryPath, $defer);
		}
		return $this;
	}

	/**
	 * @param string $source
	 * @param bool $defer
	 * @return Index|IndexBase
	 */
	public function addJS($source, $defer = true)
	{
		if (class_exists('Debug')) {
			$called = Debug::getCaller();
		} else {
			$called = '';
		}
		$fileName = $source;
		if (!contains($source, '//') && !contains($source, '?')) {    // don't download URL
			$mtime = @filemtime($source);
			if (!$mtime) {
				$mtime = @filemtime('public/' . $source);
			}
			if ($mtime) {
				$fileName .= '?' . $mtime;
			}
			$fn = new Path($fileName);
			$fileName = $fn->relativeFromAppRoot();
		}
		$defer = $defer ? 'defer="defer"' : '';
		$this->footer[$source] = '<!-- ' . $called . ' --><script src="' . $fileName . '" ' . $defer . '></script>';
		return $this;
	}

	/**
	 * @param string $source
	 * @return Index|IndexBase
	 */
	public function addCSS($source)
	{
		if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) == 'less') {
			if ($this->request->apacheModuleRewrite() && file_exists('css/.htaccess')) {
				$fileName = $source;    // rewrite inside css folder
			} else {
				$sourceCSS = str_replace('.less', '.css', $source);
				if (file_exists($sourceCSS)) {
					$fileName = $sourceCSS;
					$fileName = $this->addMtime($source);
				} else {
					$fileName = 'css/?c=Lesser&css=' . $source;
				}
			}
		} elseif (str_startsWith($source, [
			'http://', 'https://', '//',
		])) {
			$fileName = $source;
		} else {
			$fn = new Path($source);
			$fileName = $fn->relativeFromAppRoot();
			$fileName = $this->addMtime($fileName);
		}
		$this->header[$source] = '<link rel="stylesheet" type="text/css" href="' . $fileName . '" />';
		return $this;
	}

	public function addMtime($source)
	{
		if (!contains($source, '//') && !contains($source, '?')) {    // don't download URL
			$mtime = null;
			if (is_file($source)) {
				$mtime = filemtime($source);
			}
			if (!$mtime && is_file('public/' . $source)) {
				$mtime = filemtime('public/' . $source);
			}
			if ($mtime) {
				$source .= '?' . $mtime;
			}
		}
		return $source;
	}

	public function showSidebar()
	{
		TaylorProfiler::start(__METHOD__);
		$content = '';
		if (method_exists($this->controller, 'sidebar')) {
			$content = $this->controller->sidebar();
			$content = $this->s($content);
		}
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	public function renderProfiler()
	{
		$pp = new PageProfiler();
		$content = $pp->render();
		return $content;
	}

	public function implodeCSS()
	{
		$content = [];
		foreach ($this->header as $key => $script) {
			$content[] = '<!--' . $key . '-->' . "\n" . $script;
		}

		foreach ($this->footer as $key => $script) {
			$script = strip_tags($script, '<script>');
			$script = HTMLTag::parse($script);
			if ($script && $script->tag == 'script') {
				$url = $script->getAttr('src');
				if ($url) {
					// not needed because we bundle all JS
//					$content[] = '<!--' . $key . '-->' . "\n" . '<link rel="prefetch" href="' . $url . '">';
				}
			}
		}

		return implode("\n", $content) . "\n";
	}

	public function implodeJS()
	{
		if (!DEVELOPMENT) {
			$min = new MinifyJS($this->footer);
			$content = $min->implodeJS();
			if ($content) {
				return $content;
			}
		}
//		debug('footer', sizeof($this->footer));
		$content = implode("\n", $this->footer) . "\n";
		return $content;
	}

	public function addBodyClass($name)
	{
		$this->bodyClasses[$name] = $name;
	}

	/**
	 * @return string
	 */
	public function setSecurityHeaders()
	{
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

	/// to avoid Config::getInstance() if Index has a valid config
	public function getConfig()
	{
		return $this->config;
	}

}
