<?php

class Request {

	/**
	 * Assoc array of URL parameters
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var URL
	 */
	public $url;

	/**
	 * Singleton
	 * @var Request
	 */
	static protected $instance;

	protected $proxy;

	function __construct(array $array = NULL) {
		$this->data = !is_null($array) ? $array : $_REQUEST;
		if (ini_get('magic_quotes_gpc')) {
			$this->data = $this->deQuote($this->data);
		}

		$this->url = new URL(isset($_SERVER['SCRIPT_URL'])
			? $_SERVER['SCRIPT_URL']
			: (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : NULL)
		);
	}

	function deQuote(array $request) {
		foreach ($request as &$el) {
			if (is_array($el)) {
				$el = $this->deQuote($el);
			} else {
				$el = stripslashes($el);
			}
		}
		return $request;
	}

	static function getInstance($cons = NULL) {
		return self::$instance = self::$instance ? self::$instance : new self($cons);
	}

	static function getExistingInstance() {
		return self::$instance;
	}

	public static function isPHPUnit() {
		//debug($_SERVER); exit();
		$phar = !!ifsetor($_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR']);
		$loader = !!ifsetor($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER']);
		$phpStorm = basename($_SERVER['PHP_SELF']) == 'ide-phpunit.php';
		return $phar || $loader || $phpStorm;
	}

	/**
	 * Returns raw data, don't use or use with care
	 * @param $key
	 * @return mixed
	 */
	function get($key) {
		return ifsetor($this->data[$key]);
	}

	/**
	 * Will overwrite
	 * @param $var
	 * @param $val
	 */
	function set($var, $val) {
		$this->data[$var] = $val;
	}

	function un_set($name) {
		unset($this->data[$name]);
	}

	function string($name) {
		return $this->getString($name);
	}

	function getString($name) {
		return isset($this->data[$name]) ? strval($this->data[$name]) : '';
	}

	/**
	 * General filtering function
	 * @param $name
	 * @return string
	 */
	function getTrim($name) {
		$value = $this->getString($name);
		$value = strip_tags($value);
		$value = trim($value);
		return $value;
	}

	/**
	 * Will strip tags
	 * @param $name
	 * @return string
	 * @throws Exception
	 */
	function getTrimRequired($name) {
		$value = $this->getString($name);
		$value = strip_tags($value);
		$value = trim($value);
		if (!$value) {
			throw new InvalidArgumentException('Parameter '.$name.' is required.');
		}
		return $value;
	}

	/**
	 * Checks that trimmed value isset in the supplied array
	 * @param $name
	 * @param array $options
	 * @throws Exception
	 * @return string
	 */
	function getOneOf($name, array $options) {
		$value = $this->getTrim($name);
		if (!isset($options[$value])) {
			//debug($value, $options);
			throw new Exception(__METHOD__.' is throwing an exception.');
		}
		return $value;
	}

	function int($name) {
		return isset($this->data[$name]) ? intval($this->data[$name]) : 0;
	}

	function getInt($name) {
		return $this->int($name);
	}

	function getIntOrNULL($name) {
		return $this->is_set($name) ? $this->int($name) : NULL;
	}

	/**
	 * Checks for keys, not values
	 *
	 * @param $name
	 * @param array $assoc	- only array keys are used in search
	 * @return int|null
	 */
	function getIntIn($name, array $assoc) {
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			$id = NULL;
		}
		return $id;
	}

	function getIntInException($name, array $assoc) {
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			debug($id, array_keys($assoc));
			throw new Exception($name.' is not part of allowed collection.');
		}
		return $id;
	}

	function getIntRequired($name) {
		$id = $this->getIntOrNULL($name);
		if (!$id) {
			throw new Exception($name.' parameter is required.');
		}
		return $id;
	}

	function getFloat($name) {
		return floatval($this->data[$name]);
	}

	function bool($name) {
		return (isset($this->data[$name]) && $this->data[$name]) ? TRUE : FALSE;
	}

	function getBool($name) {
		return $this->bool($name);
	}

	/**
	 * Will return timestamp
	 * Converts string date compatible with strtotime() into timestamp (integer)
	 *
	 * @param string $name
	 * @throws Exception
	 * @return int
	 */
	function getTimestampFromString($name) {
		$string = $this->getTrim($name);
		$val = strtotime($string);
		if ($val == -1) {
			throw new Exception("Invalid input for date ($name): $string");
		}
		return $val;
	}

	/**
	 * @param $name
	 * @return array
	 */
	function getArray($name) {
		return isset($this->data[$name]) ? (array)($this->data[$name]) : array();
	}

	function getTrimArray($name) {
		$list = $this->getArray($name);
		if ($list) {
			$list = array_map('trim', $list);
		}
		return $list;
	}

	function getSubRequestByPath(array $name) {
		$current = $this;
		reset($name);
		do {
			$next = current($name);
			$current = $current->getSubRequest($next);
			//debug($name, $next, $current->getAll());
		} while (next($name));
		return $current;
	}

	function getArrayByPath(array $name) {
		$subRequest = $this->getSubRequestByPath($name);
		return $subRequest->getAll();
	}

	/**
	 * Makes sure it's an integer
	 * @param string $name
	 * @return int
	 */
	function getTimestamp($name) {
		return $this->getInt($name);
	}

	function is_set($name) {
		return isset($this->data[$name]);
	}

	/**
	 * Will return Time object
	 *
	 * @param string $name
	 * @param null $rel
	 * @return Time
	 */
	function getTime($name, $rel = NULL) {
		if ($this->is_set($name) && $this->getTrim($name)) {
			return new Time($this->getTrim($name), $rel);
		}
	}

	/**
	 * Will return Date object
	 *
	 * @param string $name
	 * @param null $rel
	 * @return Date
	 */
	function getDate($name, $rel = NULL) {
		if ($this->is_set($name) && $this->getTrim($name)) {
			return new Date($this->getTrim($name), $rel);
		}
		return NULL;
	}

	function getFile($name, $prefix = NULL, $prefix2 = NULL) {
		$files = $prefix ? $_FILES[$prefix] : $_FILES;
		//debug($files);
		if ($prefix2 && $files) {
			foreach ($files as &$row) {
				$row = $row[$prefix2];
			}
		}
		if ($files) {
			foreach ($files as &$row) {
				$row = $row[$name];
			}
		}
		//debug($files);
		return $files;
	}

	/**
	 * Similar to getArray() but the result is an object of a Request
	 * @param $name
	 * @return Request
	 */
	function getSubRequest($name) {
		return new Request($this->getArray($name));
	}

	/**
	 * Opposite of getSubRequest. It's a way to reimplement a subrequest
	 * @param $name
	 * @param Request $subrequest
	 * @return $this
	 */
	function import($name, Request $subrequest) {
		foreach ($subrequest->data as $key => $val) {
			$this->data[$name][$key] = $val;
		}
		return $this;
	}

	/**
	 * Returns item identified by $a or an alternative value
	 * @param $a
	 * @param $value
	 * @return string
	 */
	function getCoalesce($a, $value) {
		$a = $this->getTrim($a);
		return $a ? $a : $value;
	}

	/**
	 * List getCoalesce() but reacts on attempt to unset the value
	 * @param $a		string
	 * @param $default	string
	 * @return string
	 */
	function ifsetor($a, $default) {
		if ($this->is_set($a)) {
			$value = $this->getTrim($a);
			return $value;	// returns even if empty
		} else {
			return $default;
		}
	}

	function getControllerString($returnDefault = true) {
		if ($this->isCLI()) {
			$resolver = new CLIResolver();
			$controller = $resolver->getController();
		} else {
			$c = $this->getTrim('c');
			if ($c) {
				$resolver = new CResolver($c);
				$controller = $resolver->getController();
			} else {
				$resolver = new PathResolver();
				$controller = $resolver->getController($returnDefault);
			}
		}   // cli
		nodebug(array(
			'result' => $controller,
			'c' => $this->getTrim('c'),
			//'levels' => $this->getURLLevels(),
			'last' => isset($last) ? $last : NULL,
			'default' => class_exists('Config')
				? Config::getInstance()->defaultController
				: NULL,
			'data' => $this->data));
		return $controller;
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @throws Exception
	 * @return object
	 */
	function getController() {
		$ret = NULL;
		$c = $this->getControllerString();
		if (!$c) {
			$c = Index::getInstance()->controller; // default
		}
		if (!is_object($c)) {
			if (class_exists($c)) {
				$ret = new $c();
			} else if ($c) {
				throw new Exception('Class '.$c.' can\'t be found.');
			}
		}
		return $ret;
	}

	function setNewController($class) {
		$this->data['c'] = $class;
	}

	function getReferer() {
		if (ifsetor($_SERVER['HTTP_REFERER'])) {
			$url = new URL($_SERVER['HTTP_REFERER']);
		} else {
			$url = NULL;
		}
		return $url;
	}

	function getRefererController() {
		$return = NULL;
		$url = $this->getReferer();
		if ($url) {
			$url->setParams(array());   // get rid of any action
			$rr = $url->getRequest();
			$return = $rr->getControllerString();
		}
		//debug($_SERVER['HTTP_REFERER'], $url, $rr, $return);
		return $return;
	}

	function getRefererIfNotSelf() {
		$referer = $this->getReferer();
		$rController = $this->getRefererController();
		$index = Index::getInstance();
		$cController = $index->controller
			? get_class($index->controller)
			: Config::getInstance()->defaultController;
		$ok = (($rController != $cController) && ($referer.'' != new URL().''));
		//debug($rController, __CLASS__, $ok);
		return $ok ? $referer : NULL;
	}

	function redirect($controller, $exit = true) {
		if (class_exists('Index')
			&& Index::getInstance()
			&& method_exists(Index::getInstance(), '__destruct')) {
			Index::getInstance()->__destruct();
		}
		if (!headers_sent()
//			|| DEVELOPMENT
			&& $this->canRedirect($controller)
		) {
			ob_start();
			debug_print_backtrace(defined('DEBUG_BACKTRACE_IGNORE_ARGS')
				? DEBUG_BACKTRACE_IGNORE_ARGS : NULL);
			$bt = ob_get_clean();
			$bt = trimExplode("\n", $bt);
			foreach ($bt as $i => $line) {
				$ii = str_pad($i, 2, '0', STR_PAD_LEFT);
				header('Redirect-From-'.$ii.': ' . $line);
			}

			header('Location: '.$controller);
			echo 'Redirecting to <a href="'.$controller.'">'.$controller.'</a>';
		} else {
			$this->redirectJS($controller, DEVELOPMENT ? 0 : 0);
		}
		if ($exit && !$this->isPHPUnit()) {
			exit();
		}
	}

	function canRedirect($to) {
		if ($this->isGET()) {
			$absURL = $this->getURL();
			$absURL->makeAbsolute();
			//debug($absURL.'', $to.''); exit();
			return $absURL . '' != $to . '';
		} else {
			return true;
		}
	}

	function redirectJS($controller, $delay = 0, $message =
		'Redirecting to %1') {
		echo __($message, '<a href="'.$controller.'">'.$controller.'</a>').'
			<script>
				setTimeout(function () {
					document.location = "'.$controller.'";
				}, '.$delay.');
			</script>';
	}

	function redirectFromAjax($relative) {
		if (str_startsWith($relative, 'http')) {
			$link = $relative;
		} else {
			$link = $this->getLocation() . $relative;
		}
		if (!headers_sent()) {
			header('X-Redirect: '.$link);	// to be handled by AJAX callback
			exit();
		} else {
			$this->redirectJS($link);
		}
	}

	/**
	 * Returns the full URL to the document root of the current site
	 * @param bool $isUTF8
	 * @return URL
	 */
	static function getLocation($isUTF8 = false) {
		$docRoot = NULL;
		if (class_exists('Config')) {
			$c = Config::getInstance();
			$docRoot = $c->documentRoot;
		}
		if (!$docRoot) {
			$docRoot = self::getDocumentRoot();
		}
		//pre_print_r($docRoot);

		if (!str_startsWith($docRoot, '/')) {
			$docRoot = '/'.$docRoot;
		}

		$host = self::getHost($isUTF8);
		$url = Request::getRequestType().'://'.$host.$docRoot;
		false && pre_print_r(array(
				'c' => get_class($c),
				'docRoot' => $docRoot . '',
				'PHP_SELF' => $_SERVER['PHP_SELF'],
				'cwd' => getcwd(),
				$_SERVER,
				'url' => $url,
		));

		$url = new URL($url);
		return $url;
	}

	static function getHost($isUTF8 = false) {
		$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])
			? $_SERVER['HTTP_X_FORWARDED_HOST']
			: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL);
		if (function_exists('idn_to_utf8') && $isUTF8) {
			$try = idn_to_utf8($host);
			//debug($host, $try);
			if ($try) {
				$host = $try;
			}
		}
		return $host;
	}

	/**
	 * Returns the current page URL as is. Similar to $_SERVER['REQUEST_URI'].
	 *
	 * @return URL
	 */
	function getURL() {
		return $this->url;
	}

	/**
	 * http://php.net/manual/en/function.apache-request-headers.php#70810
	 * @return bool
	 */
	function isAjax() {
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		if (!$headers) {
			$headers = array(
				'X-Requested-With' => ifsetor($_SERVER['HTTP_X_REQUESTED_WITH'])
			);
		}
		return $this->getBool('ajax') || (
			isset($headers['X-Requested-With'])
			&&strtolower($headers['X-Requested-With']) == strtolower('XMLHttpRequest'));
	}

	function getHeader($name) {
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		return ifsetor($headers[$name]);
	}

	function getJson($name, $array = true) {
		return json_decode($this->getTrim($name), $array);
	}

	function getJSONObject($name) {
		return json_decode($this->getTrim($name));
	}

	function isSubmit() {
		return $this->isPOST() || $this->getBool('submit') || $this->getBool('btnSubmit') ;
	}

	function getDateFromYMD($name) {
		$date = $this->getInt($name);
		if ($date) {
			$y = substr($date, 0, 4);
			$m = substr($date, 4, 2);
			$d = substr($date, 6, 2);
			$date = strtotime("$y-$m-$d");
			$date = new Date($date);
		} else {
			$date = NULL;
		}
		return $date;
	}

	function getDateFromY_M_D($name) {
		$date = $this->getTrim($name);
		$date = strtotime($date);
		return $date;
	}

	/**
	 * http://www.zen-cart.com/forum/showthread.php?t=164174
	 */
	static function getRequestType() {
		$request_type =
			(((isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1'))) ||
			(isset($_SERVER['HTTP_X_FORWARDED_BY']) && strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_BY']), 'SSL') !== false) ||
			(isset($_SERVER['HTTP_X_FORWARDED_HOST']) && (strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']), 'SSL') !== false || strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']), str_replace('https://', '', HTTPS_SERVER)) !== false)) ||
			(isset($_SERVER['SCRIPT_URI']) && strtolower(substr($_SERVER['SCRIPT_URI'], 0, 6)) == 'https:') ||
			(isset($_SERVER['HTTP_X_FORWARDED_SSL']) && ($_SERVER['HTTP_X_FORWARDED_SSL'] == '1' || strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on')) ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'ssl' || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')) ||
			(isset($_SERVER['HTTP_SSLSESSIONID']) && $_SERVER['HTTP_SSLSESSIONID'] != '') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')) ||
			ifsetor($_SERVER['FAKE_HTTPS'])
			? 'https' : 'http';
		return $request_type;
	}

	function isGET() {
		return ifsetor($_SERVER['REQUEST_METHOD'], 'GET') == 'GET';
	}

	function isPOST() {
		return ifsetor($_SERVER['REQUEST_METHOD']) == 'POST';
	}

	function getAll() {
		return $this->data;
	}

	function getMethod() {
		return ifsetor($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * Will overwrite one by one.
	 * @param array $plus
	 */
	function setArray(array $plus) {
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
	}

	function getURLLevel($level) {
		$path = $this->getURLLevels();
		return isset($path[$level]) ? $path[$level] : NULL;
	}

	function getPathAfterDocRoot() {
		$config = class_exists('Config')
				? Config::getInstance()
				: new stdClass();
		$al = AutoLoad::getInstance();

		if (!$this->isWindows()) {	// linux
			//debug(getcwd(), $al->documentRoot.'');
			$url = clone $al->documentRoot;
			$url->append($this->url->getPath());
			$url->normalizeHomePage();

			$cwd = new Path(getcwd());
			$cwd->normalizeHomePage();

			$path = new Path($url);
			$path->remove($cwd);

			//debug($url.'', $cwd.'', $path.'');
		} else {	// windows
			$cwd = NULL;
			$url = new Path('');
			$url->append($this->url->getPath());
			$path = new Path($url);

			if (false) {    // doesn't work in ORS
				$path->remove(clone $al->documentRoot);
			} elseif ($al->documentRoot instanceof Path) {        // works in ORS
				$path->remove(clone $al->documentRoot);
			}
		}
		return $path;
	}

	/**
	 * @return array
	 */
	function getURLLevels() {
		$path = $this->getPathAfterDocRoot();
		//$path = $path->getURL();
		//debug($path);
		if (strlen($path) > 1) {	// "/"
			$levels = trimExplode('/', $path);
			if ($levels[0] == 'index.php') {
				array_shift($levels);
			}
		} else {
			$levels = array();
		}
		nodebug(array(
			'cwd' => getcwd(),
			//'url' => $url.'',
			'path' => $path.'',
			'getURL()' => $path->getURL().'',
			'levels' => $levels));
		return $levels;
	}

	/**
	 * Overwriting - no
	 * @param array $plus
	 * @return Request
	 */
	function append(array $plus) {
		$this->data += $plus;
		return $this;
	}

	/**
	 * Overwriting - yes
	 * @param array $plus
	 * @return Request
	 */
	function overwrite(array $plus) {
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
		return $this;
	}

	/**
	 * http://christian.roy.name/blog/detecting-modrewrite-using-php
	 * @return bool
	 */
	function apacheModuleRewrite() {
		if (function_exists('apache_get_modules')) {
			$modules = apache_get_modules();
			//debug($modules);
			$mod_rewrite = in_array('mod_rewrite', $modules);
		} else {
			$mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
		}
		return $mod_rewrite;
	}

	static function removeCookiesFromRequest() {
		if (false !== strpos(ini_get('variables_order'), 'C')) {
			//debug($_COOKIE, ini_get('variables_order'));
			foreach ($_COOKIE as $key => $_) {
				if (!isset($_GET[$key]) && !isset($_POST[$key])) {
					unset($_REQUEST[$key]);
				}
			}
		}
	}

	function getNameless($index, $alternative = NULL) {
		$levels = $this->getURLLevels();

		/* From DCI */
		// this spoils ORS menu!
/*		$controller = $this->getControllerString();
		foreach ($levels as $l => $name) {
			unset($levels[$l]);
			if ($name == $controller) {
				break;
			}
		}
		$levels = array_values($levels);	// reindex
		/* } */

		return ifsetor($levels[$index])
			? urldecode($levels[$index])    // if it contains spaces
			: $this->getTrim($alternative);
	}

	static function isCLI() {
		//return isset($_SERVER['argc']);
		return php_sapi_name() == 'cli';
	}

	/**
	 * http://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
	 * @return bool
	 */
	static function isCron() {
		return !self::isPHPUnit()
			&& self::isCLI()
			&& !isset($_SERVER['TERM'])
			&& !self::isWindows()
			;
	}

	function debug() {
		return get_object_vars($this);
	}

	/**
	 * Uses realpath() to make sure file exists
	 * @param $name
	 * @return string
	 */
	function getFilePathName($name) {
		$filename = $this->getTrim($name);
		//echo getDebug(getcwd(), $filename, realpath($filename));
		$filename = realpath($filename);
		return $filename;
	}

	/**
	 * Just cuts the folders with basename()
	 * @param $name
	 * @return string
	 */
	function getFilename($name) {
		//filter_var($this->getTrim($name), ???)
		$filename = $this->getTrim($name);
		$filename = basename($filename);
		return $filename;
	}

	/**
	 * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
	 * @see http://www.php.net/manual/en/function.getopt.php#83414
	 *
	 * Supports:
	 * -e
	 * -e <value>
	 * --long-param
	 * --long-param=<value>
	 * --long-param <value>
	 * <value>
	 *
	 * @param array $noopt List of parameters without values
	 * @return array
	 */
	function parseParameters($noopt = array()) {
		$result = array();
		$params = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		// could use getopt() here (since PHP 5.3.0), but it doesn't work reliably
		reset($params);
		while (list($tmp, $p) = each($params)) {
			if ($p{0} == '-') {
				$pname = substr($p, 1);
				$value = true;
				if ($pname{0} == '-') {
					// long-opt (--<param>)
					$pname = substr($pname, 1);
					if (strpos($p, '=') !== false) {
						// value specified inline (--<param>=<value>)
						list($pname, $value) = explode('=', substr($p, 2), 2);
					}
				}
				// check if next parameter is a descriptor or a value
				$nextparm = current($params);
				if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
				$result[$pname] = $value;
			} else {
				// param doesn't belong to any option
				$result[] = $p;
			}
		}
		return $result;
	}

	function importCLIparams($noopt = array()) {
		$this->data += $this->parseParameters($noopt);
	}

	/**
	 * http://stackoverflow.com/a/6127748/417153
	 * @return bool
	 */
	function isRefresh() {
		return isset($_SERVER['HTTP_CACHE_CONTROL']) &&
			$_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
	}

	function isCtrlRefresh() {
		return isset($_SERVER['HTTP_CACHE_CONTROL']) &&
			$_SERVER['HTTP_CACHE_CONTROL'] === 'no-cache';
	}

	public function getIntArray($name) {
		$array = $this->getArray($name);
		$array = array_map('intval', $array);
		return $array;
	}

	function clear() {
		$this->data = array();
	}

	/**
	 * [DOCUMENT_ROOT]      => U:/web
	 * [SCRIPT_FILENAME]    => C:/Users/DEPIDSVY/NetBeansProjects/merged/index.php
	 * [PHP_SELF]           => /merged/index.php
	 * [cwd]                => C:\Users\DEPIDSVY\NetBeansProjects\merged
	 * @return Path
	 */
	static function getDocumentRoot() {
		// PHP Warning:  strpos(): Empty needle in /var/www/html/vendor/spidgorny/nadlib/HTTP/class.Request.php on line 706

		0 && pre_print_r(array(
			'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
			'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
			'PHP_SELF' => $_SERVER['PHP_SELF'],
			'cwd' => getcwd(),
			'getDocumentRootByRequest' => self::getDocumentRootByRequest(),
			'getDocumentRootByDocRoot' => self::getDocumentRootByDocRoot(),
			'getDocumentRootByScript' => self::getDocumentRootByScript(),
		));

		$docRoot = self::getDocumentRootByRequest();
		if (!$docRoot || ('/' == $docRoot)) {
			$docRoot = self::getDocumentRootByDocRoot();
		}

		// this is not working right
//		if (!$docRoot || ('/' == $docRoot)) {
//			$docRoot = self::getDocumentRootByScript();
//		}

		$before = $docRoot;
		//$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot.'be', '', $docRoot);	// remove vendor/spidgorny/nadlib/be
		$docRoot = cap($docRoot, '/');
		//debug($_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['SCRIPT_FILENAME']), $before, AutoLoad::getInstance()->nadlibFromDocRoot.'be', $docRoot);
		//print '<pre>'; print_r(array($_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['SCRIPT_FILENAME']), $before, $docRoot)); print '</pre>';

		//debug_pre_print_backtrace();
		require_once __DIR__ . '/Path.php'; // needed if called early
		$docRoot = new Path($docRoot);
		//pre_print_r($docRoot, $docRoot.'');
		return $docRoot;
	}

	/**
	 * Works well with RewriteRule
	 */
	static function getDocumentRootByRequest() {
		$script = $_SERVER['SCRIPT_FILENAME'];
		$request = dirname(ifsetor($_SERVER['REQUEST_URI']));
//		exit();
		if ($request && $request != '/' && strpos($script, $request) !== false) {
			$docRootRaw = $_SERVER['DOCUMENT_ROOT'];
			$docRoot = str_replace($docRootRaw, '', dirname($script));
		} else {
			$docRoot = '/';
		}
//		pre_print_r($script, $request, strpos($script, $request), $docRoot);
		return $docRoot;
	}

	static function getDocumentRootByDocRoot() {
		$docRoot = NULL;
		$script = $_SERVER['SCRIPT_FILENAME'];
		$docRootRaw = $_SERVER['DOCUMENT_ROOT'];
		if ($docRootRaw
			&& str_startsWith($script, $docRootRaw)
			&& strpos($script, $docRootRaw) !== false) {
			$docRoot = str_replace($docRootRaw, '', dirname($script));
			//pre_print_r($docRoot);
		}
		return $docRoot;
	}

	/**
	 * @return mixed|string
	 * //~depidsvy/something
	 */
	private static function getDocumentRootByScript() {
		$script = $_SERVER['SCRIPT_FILENAME'];
		$pos = strpos($script, '/public_html');
		if ($pos !== FALSE) {
			$docRoot = substr(dirname($script), $pos);
			$docRoot = str_replace('public_html', '~depidsvy', $docRoot);
			return $docRoot;
		} else {
			$docRoot = dirname($_SERVER['PHP_SELF']);
			return $docRoot;
		}
	}

	/**
	 * @param int $age - seconds
	 */
	function setCacheable($age = 60) {
		if (!headers_sent()) {
			header('Pragma: cache');
			header('Expires: ' . date('D, d M Y H:i:s', time() + $age) . ' GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: max-age=' . $age);
		}
	}

	/**
	 * getNameless(1) doesn't provide validation.
	 * Use importNameless() to associate parameters 1, 2, 3, with their names
	 * @param array $keys
	 */
	public function importNameless(array $keys) {
		foreach ($keys as $k => $val) {
			$available = $this->getNameless($k);
			if ($available) {
				$this->data[$val] = $available;
			}
		}
	}

	/**
	 * http://stackoverflow.com/questions/738823/possible-values-for-php-os
	 * @return bool
	 */
	static function isWindows() {
		//$os = isset($_SERVER['OS']) ? $_SERVER['OS'] : '';
		//return $os == 'Windows_NT';
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	function getPOST() {
		if (isset($HTTP_RAW_POST_DATA)) {
			return $HTTP_RAW_POST_DATA;
		} else {
			return file_get_contents("php://input");
		}
	}

	function forceDownload($contentType, $filename) {
		header('Content-Type: '.$contentType);
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
	}

	public function isHTTPS() {
		return $this->getRequestType() == 'https';
	}

	public function getNamelessID() {
		$nameless = $this->getURLLevels();
		foreach ($nameless as $n) {
			if (is_numeric($n)) {
				return $n;
			}
		}
		return NULL;
	}

	public function getKeys() {
		return array_keys($this->data);
	}

	function getClientIP() {
		$ip = ifsetor($_SERVER['REMOTE_ADDR']);
		if (!$ip || in_array($ip, array(
				'127.0.0.1',
				'::1'
			))) {
			$ip = $this->fetch('http://ipecho.net/plain');
		}
		return $ip;
	}

	function fetch($url) {
		if ($this->proxy) {
			$context = stream_context_create([
				'http' => [
					'proxy' => $this->proxy,
					'timeout' => 1,
				]
			]);
			$data = file_get_contents($url, NULL, $context);
		} else {
			$context = stream_context_create([
				'http' => [
					'timeout' => 1,
				]
			]);
			$data = file_get_contents($url, NULL, $context);
		}
		return $data;
	}

	public function getGeoIP() {
		$session = new Session(__CLASS__);
		$json = $session->get(__METHOD__);
		if (!$json) {
			$url = 'http://ipinfo.io/' . $this->getClientIP();		// 166ms
			$info = $this->fetch($url);
			if ($info) {
				$json = json_decode($info);
				$session->save(__METHOD__, $json);
			} else {
				$url = 'http://freegeoip.net/json/'.$this->getClientIP();	// 521ms
				$info = $this->fetch($url);
				if ($info) {
					$json = json_decode($info);
					$json->loc = $json->latitude . ',' . $json->longitude;    // compatibility hack
					$session->save(__METHOD__, $json);
				}
			}
		}
		return $json;
	}

	public function getGeoLocation() {
		$info = $this->getGeoIP();
		return trimExplode(',', $info->loc);
	}

	public function goBack() {
		$ref = $this->getReferer();
		if ($ref) {
			$this->redirect($ref);
		}
	}

	public function setProxy($proxy) {
		$this->proxy = $proxy;
	}

	public function getBase64($string) {
		$base = $this->getTrim($string);
		return base64_decode($base);
	}

	public function getZipped($string) {
		$base = $this->getBase64($string);
		return gzuncompress($base);
	}

	public static function isCalledScript($__FILE__) {
		if (ifsetor($_SERVER['SCRIPT_FILENAME'])) {
			return $__FILE__ == $_SERVER['SCRIPT_FILENAME'];
		} else {
			throw new Exception(__METHOD__);
		}
	}

	public function getBrowserIP() {
		return $_SERVER['REMOTE_ADDR'];
	}

	public function getID() {
//		debug($this->getNamelessID(), $this->getInt('id'), $this->getURLLevels());
		$last = sizeof($this->getURLLevels()) - 1;
		return $this->getNamelessID()
			?: $this->getInt('id')
			?: $this->getNameless($last);
	}

}
