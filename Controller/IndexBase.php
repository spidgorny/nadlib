<?php

use nadlib\HTML\Messages;
use nadlib\HTTP\Session;

class IndexBase /*extends Controller*/
{    // infinite loop

	/**
	 * @var Index|IndexBE
	 */
	protected static $instance;

	/**
	 * @see Config for a public property
	 * @var LocalLangDummy
	 */
	public $ll;

	/**
	 * For any error messages during initialization.
	 *
	 * @var Messages
	 */
	public $content;

	/**
	 * @var AppController|UserlessController
	 */
	public $controller;

	public $header = [];

	public $footer = [];

	public $loadJSfromGoogle = true;

	public $template = 'template.phtml';

	public $sidebar = '';

	public $appName = 'Project name';

	public $description = '';

	public $keywords = '';

	public $bodyClasses = [];

	public $csp = [
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

	public $wrapClass = 'ui-state-error alert alert-error alert-danger padding flash flash-warn flash-error';

	/**
	 * @var UserModelInterface
	 * @public for template.phtml
	 */
	protected $user;

	protected ?\ConfigInterface $config;

	/**
	 * @var DBInterface
	 */
	protected $db;

	/**
	 * @var Request
	 */
	protected $request;

	public function __construct(?ConfigInterface $config = null)
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

	public function setSecurityHeaders(): void
	{
		if (headers_sent()) {
			return;
		}

		header('X-Frame-Options: SAMEORIGIN');
		header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
		foreach ($this->csp as $key => &$val) {
			$val = $key . ' ' . implode(' ', $val);
		}

		header('Content-Security-Policy: ' . implode('; ', $this->csp));
		header('X-Content-Security-Policy: ' . implode('; ', $this->csp));
	}

	/**
     * TODO: Remove the boolean parameter from getInstance()
     * TODO: And force to use makeInstance() in case it was true
     * @return Index|IndexBE
     * @throws Exception
     */
    public static function makeInstance(?Config $config = null)
	{
		return static::getInstance(true, $config);
	}

	/**
     * @param bool $createNew - must be false
     * @return Index
     * @throws Exception
     */
    public static function getInstance($createNew = false, ?ConfigInterface $config = null)
	{
		TaylorProfiler::start(__METHOD__);
		$instance = self::$instance ?: null;
		if (!$instance && $createNew) {
			$static = get_called_class();
			$instance = new $static($config);
			self::$instance = $instance;
		}

		TaylorProfiler::stop(__METHOD__);
		return $instance;
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

	/**
	 * Called by index.php explicitly,
	 * therefore processes exceptions.
	 *
	 * That's not true anymore, called in render().
	 * @throws Exception
	 */
	public function initController(): void
	{
		// already created
		if ($this->controller instanceof Controller) {
			return;
		}

		$slug = $this->request->getControllerString();
//		llog('initController slug', $slug);
		if (!$slug) {
			throw new Exception404($slug);
		}

		if ($_REQUEST['d']) {
			$this->log(__METHOD__, $slug);
		}

		$this->loadController($slug);
		$this->bodyClasses[] = is_object($this->controller) ? get_class($this->controller) : '';
		TaylorProfiler::stop(__METHOD__);
	}


	/**
	 * Move it to the MRBS
	 * @param string $action
	 * @param mixed $data
	 */
	public function log($action, $data): void
	{
		//debug($action, $bookingID);
		/*$this->db->runInsertQuery('log', array(
			'who' => $this->user->id,
			'action' => $action,
			'booking' => $bookingID,
		));*/
	}

	/**
	 * Usually autoload is taking care of the loading, but sometimes you want to check the path.
	 * Will call postInit() of the controller if available.
	 * @param string $class
	 * @throws Exception
	 */
	protected function loadController($class)
	{
		$slugParts = explode('/', $class);
		$class = end($slugParts);    // again, because __autoload needs the full path
//		llog(__METHOD__, $slugParts, $class, class_exists($class));
		if (class_exists($class)) {
			$this->controller = $this->makeController($class);
			return $this->controller;
		}

        //debug($_SESSION['autoloadCache']);
		$exception = 'Class ' . $class . ' not found. Dev hint: try clearing autoload cache?';
		unset($_SESSION['AutoLoad']);
		throw new Exception404($exception);
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

//			$this->controller = new $class();
		// debug($class, get_class($this->controller));
		if (method_exists($this->controller, 'postInit')) {
			$this->controller->postInit();
		}

		return $this->controller;
	}

	public function render(): string
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
		} catch (Exception $exception) {    // handles ALL exceptions
			$content = $this->renderException($exception);
		}

		$content = $this->renderTemplateIfNotAjax($content);
		TaylorProfiler::stop(__METHOD__);
		return $content . $this->s($this->renderProfiler());
	}

	/**
	 * @throws AccessDeniedException
	 */
	public function initSession(): void
	{
//		debug('is session started', session_id(), session_status());
		if (!Request::isCLI() && !Session::isActive() && !headers_sent()) {
			ini_set('session.use_trans_sid', false);
			ini_set('session.use_only_cookies', true);
			ini_set('session.cookie_httponly', true);
			ini_set('session.hash_bits_per_character', 6);
			ini_set('session.hash_function', 'sha512');
			llog('session_start in initSession');
			$ok = session_start();
			if (!$ok) {
				throw new RuntimeException('session_start() failed');
			}
		}

		$this->setSecurityHeaders();
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
	 * @throws ReflectionException
	 */
	public function renderController(): string|array
	{
		TaylorProfiler::start(__METHOD__);
		$content = '';
		$method = PHP_SAPI === 'cli'
			? $this->getMethodFromCli()
//			: $this->getMethodFromWeb();
			: 'render';  // controller's render() should deal with performAction
//		llog('method', $method);

		// delegate the decision which action to call to the controller
		// this makes render() function not working
//		if ($method && method_exists($this->controller, 'performAction')) {
//			$content = $this->controller->performAction($method);
//		} else

		if ($method && method_exists($this->controller, $method)) {
			//echo 'Method: ', $method, BR;
			//$params = array_slice($_SERVER['argv'], 3);
			//debug($this->request->getAll());
			$marshal = new MarshalParams($this->controller);
			$content = $marshal->call($method);
			//$content = $this->controller->$method();
		} elseif (!is_numeric($method)) {    // this is a parameter
			$content = $this->renderException(
				new InvalidArgumentException('Method [' . $method . '] is not callable on ' .
					get_class($this->controller))
			);
		}

		$content = $this->s($content);
		$this->sidebar = $this->showSidebar();
		if ($this->controller->layout instanceof Wrap
			&& !$this->request->isAjax()) {
			/** @var Wrap $this->controller->layout */
			$content = $this->controller->layout->wrap($content);
			$content = str_replace('###SIDEBAR###', $this->showSidebar(), $content);
		}

		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	public function getMethodFromCli()
	{
		llog('argv', $_SERVER['argv']);
		$notOptions = array_filter(
			array_slice(
				ifsetor($_SERVER['argv'], []),
				1
			),
			static function ($el): bool {
				if (is_numeric($el)) {
					return false;
				}

				return $el[0] !== '-';    // --options
			}
		);
		llog('notOptions', $notOptions);
		// $notOptions[0] is the controller
		return ifsetor($notOptions[1], 'render');
	}

	/**
     * Does not catch LoginException - show your login form in Index
     * @param string $wrapClass
     * @return string
     */
    public function renderException(Exception $e, $wrapClass = 'ui-state-error alert alert-error alert-danger padding flash flash-warn flash-error'): string|\JSONResponse
	{
		if (Request::isCLI()) {
			echo get_class($e),
			' #', $e->getCode(),
			': ', $e->getMessage(), BR;
			echo $e->getTraceAsString(), BR;
			return '';
		}

		if ($this->controller) {
			$this->controller->title = get_class($this->controller);
		}

		$re = new RenderException($e);
		return $re->render($this->wrapClass);
	}

	public function s($content): string
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	public function showSidebar()
	{
		TaylorProfiler::start(__METHOD__);
		$content = '';
		if (method_exists($this->controller, 'sidebar')) {
			try {
				$content = $this->controller->sidebar();
				$content = $this->s($content);
			} catch (Exception $e) {
				// no sidebar
			}
		}

		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	/**
     * @return mixed[]
     */
    public function renderTemplateIfNotAjax($content): array
	{
		$contentOut = [];
//		llog('renderTemplateIfNotAjax', gettype($content));
		if (!$this->request->isAjax() && !Request::isCLI()) {
			// display Exception
			$view = $this->renderTemplate($content);
			//echo gettype2($view), BR;
			$contentOut[] = $view instanceof View ? $view->render() : $view;
		} else {
			//$contentOut .= $this->content;    // NO! it's JSON (maybe)
			$contentOut[] = $this->s($content);
		}

		return $contentOut;
	}

	public function renderTemplate($content): \View
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
		$v->title = $this->controller ? strip_tags(ifsetor($this->controller->title)) : null;
		$v->sidebar = $this->sidebar;
		$v->baseHref = $this->request->getLocation();
		//$lf = new LoginForm('inlineForm');	// too specific - in subclass
		//$v->loginForm = $lf->dispatchAjax();
		TaylorProfiler::stop(__METHOD__);
		return $v;
	}

	public function renderProfiler(): array
	{
		$pp = new PageProfiler();
		return $pp->render();
	}

	public function getMethodFromWeb(): string
	{
		$method = ifsetor($_REQUEST['action']);
		return $method ? $method . 'Action' : 'render';
	}

	public function __destruct()
	{
//		if (is_object($this->user) && method_exists($this->user, '__destruct')) {
		// called automatically(!)
		//$this->user->__destruct();
//		}
	}

	public function message($text): string
	{
		return $this->content->message($text);
	}

	public function error($text): string
	{
		return $this->content->error($text);
	}

	public function success($text): string
	{
		return $this->content->success($text);
	}

	public function info($text): string
	{
		return $this->content->info($text);
	}

	public function addJQueryUI(): static
	{
		$this->addJQuery();
		if (ifsetor($this->footer['jqueryui.js'])) {
			return $this;
		}

		$al = AutoLoad::getInstance();
		$jQueryPath = clone $al->componentsPath;
		//debug($jQueryPath);
		//$jQueryPath->appendString('jquery-ui/ui/minified/jquery-ui.min.js');
		$jQueryPath->appendString('jquery-ui/jquery-ui.min.js');
		$jQueryPath->setAsFile();

		$appRoot = $al->getAppRoot();
		nodebug([
			'jQueryPath' => $jQueryPath,
			'jQueryPath->exists()' => $jQueryPath->exists(),
			'appRoot' => $appRoot,
			'componentsPath' => $al->componentsPath,
			'fe(jQueryPath)' => file_exists($jQueryPath->getUncapped()),
			'fe(appRoot)' => file_exists($appRoot . $jQueryPath->getUncapped()),
			'fe(nadlibFromDocRoot)' => file_exists($al->nadlibFromDocRoot . $jQueryPath),
			'fe(componentsPath)' => file_exists($al->componentsPath . $jQueryPath),
			'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
			'documentRoot' => $al->documentRoot,
			'componentsPath.jQueryPath' => $al->componentsPath . $jQueryPath,
		]);
		if (DEVELOPMENT || !$this->loadJSfromGoogle) {
			if ($jQueryPath->exists()) {
				$this->addJS($jQueryPath->relativeFromAppRoot()->getUncapped());
				return $this;
			}

			$jQueryPath = clone $al->componentsPath;
			$jQueryPath->appendString('jquery-ui/jquery-ui.js');
			$jQueryPath->setAsFile();
			if ($jQueryPath->exists()) {
				$this->addJS($jQueryPath->relativeFromAppRoot()->getUncapped());
				return $this;
			}
		}

		// commented out because this should be project specific
		//$this->addCSS('components/jquery-ui/themes/ui-lightness/jquery-ui.min.css');
		$this->footer['jqueryui.js'] = '<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
		<script>window.jQueryUI || document.write(\'<script src="' . $jQueryPath . '"><\/script>\')</script>';
		$this->addCSS('http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/ui-lightness/jquery-ui.css');
		return $this;
	}

	/**
     * @return $this
     */
    public function addJQuery(array $props = ['defer' => true]): static
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
				$this->addJS(cap($al->componentsPath->getURL()) . $jQueryPath, $props);
				return $this;
			} elseif (file_exists($appRoot . $jQueryPath)) {
				// does not work if both paths are the same!!
//				$rel = Path::make(getcwd())->remove($al->appRoot);
				$rel = Path::make(Config::getInstance()->documentRoot)->remove($appRoot);
				$rel->trimIf('be');
				$rel->reverse();
				$this->addJS($rel . $jQueryPath, $props);
				return $this;
			} elseif (file_exists($al->nadlibFromDocRoot . $jQueryPath)) {
				$this->addJS($al->nadlibFromDocRoot . $jQueryPath, $props);
				return $this;
			} else {
				$jQueryPath = 'node_modules/jquery/dist/jquery.min.js';
			}

			$this->addJS($jQueryPath, $props);
		}

		return $this;
	}

	/**
     * @return Index|IndexBase
     */
    public function addJS(string $source, array $props = ['defer' => true]): static
	{
		$called = class_exists('Debug') ? Debug::getCaller() : '';

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

		$this->footer[$source] = '<!-- ' . $called . ' --><script src="' . $fileName . '" ' . HTMLTag::renderAttr($props) . '></script>';
		return $this;
	}

	/**
     * @return Index|IndexBase
     */
    public function addCSS(string $source): static
	{
		if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'less') {
			if ($this->request->apacheModuleRewrite() && file_exists('css/.htaccess')) {
				$fileName = $source;    // rewrite inside css folder
			} else {
				$sourceCSS = str_replace('.less', '.css', $source);
				if (file_exists($sourceCSS)) {
					//$fileName = $sourceCSS;
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

	public function addMtime(string $source): string
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


	public function implodeCSS(): string
	{
		$content = [];
		foreach ($this->header as $key => $script) {
			$content[] = '<!--' . $key . '-->' . "\n" . $script;
		}

		foreach ($this->footer as $script) {
			$script = strip_tags($script, '<script>');
			$script = HTMLTag::parse($script);
			if ($script && $script->tag === 'script') {
				$url = $script->getAttr('src');
				if ($url) {
					// not needed because we bundle all JS
//					$content[] = '<!--' . $key . '-->' . "\n" . '<link rel="prefetch" href="' . $url . '">';
				}
			}
		}

		return implode("\n", $content) . "\n";
	}

	public function implodeJS(): string
	{
		if (!DEVELOPMENT) {
			$min = new MinifyJS($this->footer);
			$content = $min->implodeJS();
			if ($content) {
				return $content;
			}
		}

//		debug('footer', sizeof($this->footer));
		return implode("\n", $this->footer) . "\n";
	}

	public function addBodyClass($name): void
	{
		$this->bodyClasses[$name] = $name;
	}

	/// to avoid Config::getInstance() if Index has a valid config
	public function getConfig(): ?\ConfigInterface
	{
		return $this->config;
	}

}
