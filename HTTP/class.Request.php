<?php

class Request
{

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

	function __construct(array $array = NULL)
	{
		$this->data = !is_null($array) ? $array : $_REQUEST;
		if (ini_get('magic_quotes_gpc')) {
			$this->data = $this->deQuote($this->data);
		}

		$this->url = new URL(isset($_SERVER['SCRIPT_URL'])
			? $_SERVER['SCRIPT_URL']
			: $_SERVER['REQUEST_URI']);
	}

	function deQuote(array $request)
	{
		foreach ($request as &$el) {
			if (is_array($el)) {
				$el = $this->deQuote($el);
			} else {
				$el = stripslashes($el);
			}
		}
		return $request;
	}

	static function getInstance()
	{
		return self::$instance = self::$instance ? self::$instance : new self();
	}

	static function getExistingInstance()
	{
		return self::$instance;
	}

	/**
	 * Will overwrite
	 * @param $var
	 * @param $val
	 */
	function set($var, $val)
	{
		$this->data[$var] = $val;
	}

	function un_set($name)
	{
		unset($this->data[$name]);
	}

	function string($name)
	{
		return $this->getString($name);
	}

	function getString($name)
	{
		return isset($this->data[$name]) ? strval($this->data[$name]) : '';
	}

	/**
	 * General filtering function
	 * @param $name
	 * @return string
	 */
	function getTrim($name)
	{
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
	function getTrimRequired($name)
	{
		$value = $this->getString($name);
		$value = strip_tags($value);
		$value = trim($value);
		if (!$value) {
			throw new Exception('Parameter ' . $name . ' is required.');
		}
		return $value;
	}

	/**
	 * Checks that trimmed value isset in the supplied array
	 * @param $name
	 * @param array $options
	 * @return string
	 * @throws Exception
	 */
	function getOneOf($name, array $options)
	{
		$value = $this->getTrim($name);
		if (!isset($options[$value])) {
			//debug($value, $options);
			throw new Exception(__METHOD__ . ' is throwing an exception.');
		}
		return $value;
	}

	function int($name)
	{
		return isset($this->data[$name]) ? intval($this->data[$name]) : 0;
	}

	function getInt($name)
	{
		return $this->int($name);
	}

	function getIntOrNULL($name)
	{
		return $this->is_set($name) ? $this->int($name) : NULL;
	}

	/**
	 * Checks for keys, not values
	 *
	 * @param $name
	 * @param array $assoc - only array keys are used in search
	 * @return int|null
	 */
	function getIntIn($name, array $assoc)
	{
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			$id = NULL;
		}
		return $id;
	}

	function getIntInException($name, array $assoc)
	{
		$id = $this->getIntOrNULL($name);
		if (!is_null($id) && !in_array($id, array_keys($assoc))) {
			debug($id, array_keys($assoc));
			throw new Exception($name . ' is not part of allowed collection.');
		}
		return $id;
	}

	function getIntRequired($name)
	{
		$id = $this->getIntOrNULL($name);
		if (!$id) {
			throw new Exception($name . ' parameter is required.');
		}
		return $id;
	}

	function getFloat($name)
	{
		return floatval($this->data[$name]);
	}

	function bool($name)
	{
		return $this->data[$name] ? TRUE : FALSE;
	}

	function getBool($name)
	{
		return $this->bool($name);
	}

	/**
	 * Will return timestamp
	 * Converts string date compatible with strtotime() into timestamp (integer)
	 *
	 * @param string $name
	 * @return int
	 * @throws Exception
	 */
	function getTimestampFromString($name)
	{
		$string = $this->getTrim($name);
		$val = strtotime($string);
		if ($val == -1) {
			throw new Exception("Invalid input for date ($name): $string");
		}
		return $val;
	}

	function getArray($name)
	{
		return isset($this->data[$name]) ? (array)($this->data[$name]) : array();
	}

	function getTrimArray($name)
	{
		$list = $this->getArray($name);
		if ($list) {
			$list = array_map('trim', $list);
		}
		return $list;
	}

	function getSubRequestByPath(array $name)
	{
		$current = $this;
		reset($name);
		do {
			$next = current($name);
			$current = $current->getSubRequest($next);
			//debug($name, $next, $current->getAll());
		} while (next($name));
		return $current;
	}

	function getArrayByPath(array $name)
	{
		$subRequest = $this->getSubRequestByPath($name);
		return $subRequest->getAll();
	}

	/**
	 * Makes sure it's an integer
	 * @param string $name
	 * @return int
	 */
	function getTimestamp($name)
	{
		return $this->getInt($name);
	}

	function is_set($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * Will return Time object
	 *
	 * @param string $name
	 * @param null $rel
	 * @return Time
	 */
	function getTime($name, $rel = NULL)
	{
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
	function getDate($name, $rel = NULL)
	{
		if ($this->is_set($name) && $this->getTrim($name)) {
			return new Date($this->getTrim($name), $rel);
		}
	}

	function getFile($name, $prefix = NULL, $prefix2 = NULL)
	{
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
	function getSubRequest($name)
	{
		return new Request($this->getArray($name));
	}

	/**
	 * Opposite of getSubRequest. It's a way to reimplement a subrequest
	 * @param $name
	 * @param Request $subrequest
	 * @return $this
	 */
	function import($name, Request $subrequest)
	{
		foreach ($subrequest->data as $key => $val) {
			$this->data[$name][$key] = $val;
		}
		return $this;
	}

	function getCoalesce($a, $b)
	{
		$a = $this->getTrim($a);
		return $a ? $a : $b;
	}

	function getControllerString()
	{
		if ($this->isCLI()) {
			$controller = $_SERVER['argv'][1];
			$this->data += $this->parseParameters();
			//debug($this->data);
		} else {
			$controller = $this->getTrim('c');
			if ($controller) {
				// to simplofy URL it first searches for the corresponding controller
				$ptr = &Config::getInstance()->config['autoload']['notFoundException'];
				$tmp = $ptr;
				$ptr = false;
				if ($controller && class_exists($controller . 'Controller')) {
					$controller = $controller . 'Controller';
				}
				$ptr = $tmp;
				//$controller = end(explode('/', $controller)); // in case it's with subfolder
				// ^ commented as subfolders need be used for BEmenu
			} else {
				$levels = $this->getURLLevels();
				if ($levels) {
					$levels = array_reverse($levels);
					foreach ($levels as $class) {
						// RewriteRule should not contain "?c="
						nodebug(
							$class,
							class_exists($class . 'Controller'),
							class_exists($class));
						// to simplify URL it first searches for the corresponding controller
						if ($class && class_exists($class . 'Controller')) {    // this is untested
							$last = $class . 'Controller';
							break;
						}
						if (class_exists($class)) {
							$last = $class;
							break;
						}
					}
					$controller = $last;
				} else {
					$controller = Config::getInstance()->defaultController;    // not good as we never get 404
				}
			}
		}   // cli
		nodebug(array(
			'result' => $controller,
			'c' => $this->getTrim('c'),
			'levels' => $this->getURLLevels(),
			'last' => $last,
			'default' => Config::getInstance()->defaultController,
			'data' => $this->data));
		return $controller;
	}

	/**
	 * Will require modifications when realurl is in place
	 *
	 * @return object
	 * @throws Exception
	 */
	function getController()
	{
		$c = $this->getControllerString();
		if (!$c) {
			$c = $GLOBALS['i']->controller; // default
		}
		if (!is_object($c)) {
			if (class_exists($c)) {
				$ret = new $c();
			} else if ($c) {
				throw new Exception('Class ' . $c . ' can\'t be found.');
			}
		}
		return $ret;
	}

	function setNewController($class)
	{
		$this->data['c'] = $class;
	}

	function getReferer()
	{
		return new URL($_SERVER['HTTP_REFERER']);
	}

	function getRefererController()
	{
		$url = $this->getReferer();
		$rr = $url->getRequest();
		$return = $rr->getControllerString();
		//debug($_SERVER['HTTP_REFERER'], $url, $rr, $return);
		return $return ? $return : Config::getInstance()->defaultController;
	}

	function redirect($controller)
	{
		if (class_exists('Index') && Index::getInstance() && method_exists(Index::getInstance(), '__destruct')) {
			Index::getInstance()->__destruct();
		}
		if (!headers_sent()
//			|| DEVELOPMENT
		) {
			header('Location: ' . $controller);
			exit();
		} else {
			$this->redirectJS($controller);
		}
	}

	function redirectJS($controller)
	{
		echo 'Redirecting to <a href="' . $controller . '">' . $controller . '</a>
			<script>
				document.location = "' . $controller . '";
			</script>';
		exit();
	}

	/**
	 * Returns the full URL to the document root of the current site
	 * @return string
	 */
	static function getLocation()
	{
		if (class_exists('Config')) {
			$c = Config::getInstance();
			$docRoot = $c->documentRoot;
		} else {
			$docRoot = dirname($_SERVER['PHP_SELF']);
		}
		if (strlen($docRoot) == 1) {
			$docRoot = '/';
		} else {
			$docRoot .= '/';
		}
		$url = Request::getRequestType() . '://' . (
			$_SERVER['HTTP_X_FORWARDED_HOST']
				? $_SERVER['HTTP_X_FORWARDED_HOST']
				: $_SERVER['HTTP_HOST']
			) . $docRoot;
		//$GLOBALS['i']->content .= $url;
		//debug($url);
		return $url;
	}

	/**
	 * Returns the current page URL as is. Similar to $_SERVER['REQUEST_URI'].
	 *
	 * @return URL
	 */
	function getURL()
	{
		return $this->url;
	}

	function isAjax()
	{
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		return $this->getBool('ajax') || (strtolower($headers['X-Requested-With']) == strtolower('XMLHttpRequest'));
	}

	function getHeader($name)
	{
		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		return $headers[$name];
	}

	function getJson($name, $array = true)
	{
		return json_decode($this->getTrim($name), $array);
	}

	function getJSONObject($name)
	{
		return json_decode($this->getTrim($name));
	}

	function isSubmit()
	{
		return $this->isPOST() || $this->getBool('submit') || $this->getBool('btnSubmit');
	}

	function getDateFromYMD($name)
	{
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

	/**
	 * http://www.zen-cart.com/forum/showthread.php?t=164174
	 */
	static function getRequestType()
	{
		$request_type =
			(((isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1'))) ||
				(isset($_SERVER['HTTP_X_FORWARDED_BY']) && strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_BY']), 'SSL') !== false) ||
				(isset($_SERVER['HTTP_X_FORWARDED_HOST']) && (strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']), 'SSL') !== false || strpos(strtoupper($_SERVER['HTTP_X_FORWARDED_HOST']), str_replace('https://', '', HTTPS_SERVER)) !== false)) ||
				(isset($_SERVER['SCRIPT_URI']) && strtolower(substr($_SERVER['SCRIPT_URI'], 0, 6)) == 'https:') ||
				(isset($_SERVER['HTTP_X_FORWARDED_SSL']) && ($_SERVER['HTTP_X_FORWARDED_SSL'] == '1' || strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on')) ||
				(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'ssl' || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')) ||
				(isset($_SERVER['HTTP_SSLSESSIONID']) && $_SERVER['HTTP_SSLSESSIONID'] != '') ||
				(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')) ? 'https' : 'http';
		return $request_type;
	}

	function isPOST()
	{
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	function getAll()
	{
		return $this->data;
	}

	function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Will overwrite.
	 * @param array $plus
	 */
	function setArray(array $plus)
	{
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
	}

	function getURLLevel($level)
	{
		$path = $this->getURLLevels();
		return isset($path[$level]) ? $path[$level] : NULL;
	}

	/**
	 * @return array
	 */
	function getURLLevels()
	{
		$path = $this->url->getPath();
		if (strlen($path) > 1) {    // "/"
			$path = trimExplode('/', $path);
			if ($path[0] == 'index.php') {
				array_shift($path);
			}
			//debug($this->url->getPath(), $path);
		} else {
			$path = array();
		}
		return $path;
	}

	/**
	 * Overwriting - no
	 * @param array $plus
	 * @return Request
	 */
	function append(array $plus)
	{
		$this->data += $plus;
		return $this;
	}

	/**
	 * Overwriting - yes
	 * @param array $plus
	 * @return Request
	 */
	function overwrite(array $plus)
	{
		foreach ($plus as $key => $val) {
			$this->data[$key] = $val;
		}
		return $this;
	}

	/**
	 * http://christian.roy.name/blog/detecting-modrewrite-using-php
	 * @return bool
	 */
	function apacheModuleRewrite()
	{
		if (function_exists('apache_get_modules')) {
			$modules = apache_get_modules();
			//debug($modules);
			$mod_rewrite = in_array('mod_rewrite', $modules);
		} else {
			$mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
		}
		return $mod_rewrite;
	}

	static function removeCookiesFromRequest()
	{
		if (false !== strpos(ini_get('variables_order'), 'C')) {
			//debug($_COOKIE, ini_get('variables_order'));
			foreach ($_COOKIE as $key => $_) {
				if (isset($_GET[$key])) {
					$_REQUEST[$key] = $_GET[$key];
				} else if (isset($_POST[$key])) {
					$_REQUEST[$key] = $_POST[$key];
				}

				if (isset($_REQUEST[$key])) {
					unset($_REQUEST[$key]);
				}
			}
		}
	}

	function getNameless($index, $alternative = NULL)
	{
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

		return $levels[$index] ? $levels[$index] : $this->getTrim($alternative);
	}

	static function isCLI()
	{
		//return isset($_SERVER['argc']);
		return php_sapi_name() == 'cli';
	}

	/**
	 * http://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
	 * @return bool
	 */
	static function isCron()
	{
		return !isset($_SERVER['TERM']);
	}

	function debug()
	{
		return get_object_vars($this);
	}

	/**
	 * Uses realpath() to make sure file exists
	 * @param $name
	 * @return string
	 */
	function getFilePathName($name)
	{
		$filename = $this->getTrim($name);
		//debug(getcwd(), $filename, realpath($filename));
		$filename = realpath($filename);
		return $filename;
	}

	/**
	 * Just cuts the folders with basename()
	 * @param $name
	 * @return string
	 */
	function getFilename($name)
	{
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
	function parseParameters($noopt = array())
	{
		$result = array();
		$params = $GLOBALS['argv'] ? $GLOBALS['argv'] : array();
		// could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
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

	function importCLIparams($noopt = array())
	{
		$this->data += $this->parseParameters($noopt);
	}

	/**
	 * http://stackoverflow.com/a/6127748/417153
	 * @return bool
	 */
	function isRefresh()
	{
		return isset($_SERVER['HTTP_CACHE_CONTROL']) &&
			$_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0';
	}

	public function getIntArray($name)
	{
		$array = $this->getArray($name);
		$array = array_map('intval', $array);
		return $array;
	}

	function clear()
	{
		$this->data = array();
	}

	static function getDocumentRoot()
	{
		// PHP Warning:  strpos(): Empty needle in /var/www/html/vendor/spidgorny/nadlib/HTTP/class.Request.php on line 706
		if ($_SERVER['DOCUMENT_ROOT'] &&
			strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) !== false) {
			$docRoot = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
		} else {    //~depidsvy/something
			$pos = strpos($_SERVER['SCRIPT_FILENAME'], '/public_html');
			$docRoot = substr(dirname($_SERVER['SCRIPT_FILENAME']), $pos);
			$docRoot = str_replace('public_html', '~depidsvy', $docRoot);
		}
		$before = $docRoot;
		//$docRoot = str_replace(AutoLoad::getInstance()->nadlibFromDocRoot.'be', '', $docRoot);	// remove vendor/spidgorny/nadlib/be
		//debug($_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['SCRIPT_FILENAME']), $before, AutoLoad::getInstance()->nadlibFromDocRoot.'be', $docRoot);
		return $docRoot;
	}

	function setCacheable($age = 60)
	{
		header('Pragma: cache');
		header('Expires: ' . date('D, d M Y H:i:s', time() + $age) . ' GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: max-age=' . $age);
	}

	/**
	 * getNameless(1) doesn't provide validation.
	 * Use importNameless() to associate parameters 1, 2, 3, with their names
	 * @param array $keys
	 */
	public function importNameless(array $keys)
	{
		foreach ($keys as $k => $val) {
			$this->data[$val] = $this->getNameless($k);
		}
	}

	/**
	 * http://stackoverflow.com/questions/738823/possible-values-for-php-os
	 * @return bool
	 */
	static function isWindows()
	{
		//$os = isset($_SERVER['OS']) ? $_SERVER['OS'] : '';
		//return $os == 'Windows_NT';
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

}
