<?php

require_once __DIR__ . '/URL.php';

use nadlib\HTTP\Session;
use spidgorny\nadlib\HTTP\URL;

class Request
{
	/**
	 * Singleton
	 * @var Request
	 */
	protected static $instance;
	/**
	 * @var URL
	 */
	public $url;
	/**
	 * Assoc array of URL parameters
	 * @var array
	 */
	protected $data = [];
	protected $proxy;

	public function __construct(array $array = null)
	{
		$this->data = !is_null($array) ? $array : $_REQUEST;
		if (ini_get('magic_quotes_gpc')) {
			$this->data = $this->deQuote($this->data);
		}

		$this->url = new URL(
			isset($_SERVER['SCRIPT_URL'])
				? $_SERVER['SCRIPT_URL']
				: (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null),
			$this->data
		);
	}

	public function deQuote(array $request)
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

	public static function getExistingInstance()
	{
		return static::$instance;
	}

	public static function isJenkins()
	{
		return ifsetor($_SERVER['BUILD_NUMBER'], getenv('BUILD_NUMBER'));
	}

	public static function getLocationDebug()
	{
		$docRoot = self::getDocRoot();
		ksort($_SERVER);
		pre_print_r([
			'docRoot' => $docRoot . '',
			'PHP_SELF' => $_SERVER['PHP_SELF'],
			'cwd' => getcwd(),
			'url' => self::getLocation() . '',
			'server' => array_filter($_SERVER, function ($el) {
				return is_string($el) && strpos($el, '/') !== false;
			}),
		]);
	}

	/**
	 * @return Path
	 */
	public static function getDocRoot()
	{
		$docRoot = null;
		if (class_exists('Config')) {
			$c = Config::getInstance();
			$docRoot = $c->documentRoot;
		}
		if (!$docRoot) {
			$docRoot = self::getDocumentRoot();
		}
		//pre_print_r($docRoot);

		if (!str_startsWith($docRoot, '/')) {
			$docRoot = '/' . $docRoot;
		}

		if (!($docRoot instanceof Path)) {
			$docRoot = new Path($docRoot);
		}

		return $docRoot;
	}

	public static function getInstance($cons = null)
	{
		if (!static::$instance) {
			static::$instance = new static($cons);
		}
		return static::$instance;
	}

	/**
	 * [DOCUMENT_ROOT]      => U:/web
	 * [SCRIPT_FILENAME]    => C:/Users/DEPIDSVY/NetBeansProjects/merged/index.php
	 * [PHP_SELF]           => /merged/index.php
	 * [cwd]                => C:\Users\DEPIDSVY\NetBeansProjects\merged
	 * @return Path
	 */
	public static function getDocumentRoot()
	{
		// PHP Warning:  strpos(): Empty needle in /var/www/html/vendor/spidgorny/nadlib/HTTP/class.Request.php on line 706

		$docRoot = self::getDocumentRootByRequest();
		if (!$docRoot || ('/' == $docRoot)) {
			$docRoot = self::getDocumentRootByDocRoot();
		}

		// this is not working right
		//		if (!$docRoot || ('/' == $docRoot)) {
		//			$docRoot = self::getDocumentRootByScript();
		//		}

		//		$before = $docRoot;
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
	public static function getDocumentRootByRequest()
	{
		$script = $_SERVER['SCRIPT_FILENAME'];
		$request = dirname(ifsetor($_SERVER['REQUEST_URI'], ''));
		//		exit();
		if ($request && $request !== '/' && strpos($script, $request) !== false) {
			$docRootRaw = $_SERVER['DOCUMENT_ROOT'];
			$docRoot = str_replace($docRootRaw, '', dirname($script)) . '/';    // dirname() removes slash
		} else {
			$docRoot = '/';
		}
		//		pre_print_r($script, $request, strpos($script, $request), $docRoot);
		return $docRoot;
	}

	public static function getDocumentRootByDocRoot()
	{
		$docRoot = null;
		$script = $_SERVER['SCRIPT_FILENAME'];
		$docRootRaw = ifsetor($_SERVER['DOCUMENT_ROOT']);
		if (!empty($docRootRaw)) {
			$beginTheSame = str_startsWith($script, $docRootRaw);
			$contains = strpos($script, $docRootRaw) !== false;
		} else {
			$beginTheSame = false;
			$contains = false;
		}
		if ($docRootRaw
			&& $beginTheSame
			&& $contains
		) {
			$docRoot = str_replace($docRootRaw, '', dirname($script) . '/');    // slash is important
			//pre_print_r($docRoot);
		}
		0 && pre_print_r([
			'script' => $script,
			'docRootRaw' => $docRootRaw,
			'beginTheSame' => $beginTheSame,
			'contains' => $contains,
			'replaceFrom' => dirname($script),
			'docRoot' => $docRoot,
		]);
		return $docRoot;
	}

	//

	/**
	 * Returns the full URL to the document root of the current site
	 * @param bool $isUTF8
	 * @return URL
	 */
	public static function getLocation($isUTF8 = false)
	{
		$docRoot = self::getDocRoot();
//		llog($docRoot.'');
		$host = self::getHost($isUTF8);
		$url = Request::getRequestType() . '://' . $host . $docRoot;
		$url = new URL($url);
		return $url;
	}

	public static function getHost($isUTF8 = false)
	{
		if (self::isCLI()) {
			return gethostname();
		}
		$host = ifsetor($_SERVER['HTTP_X_ORIGINAL_HOST']);
		if (!$host) {
			$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])
				? $_SERVER['HTTP_X_FORWARDED_HOST']
				: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
		}
		if (function_exists('idn_to_utf8') && $isUTF8) {
			if (phpversion() >= 7.3) {
				$try = idn_to_utf8($host, 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 1);
			} else {
				$try = idn_to_utf8($host);
			}
			//debug($host, $try);
			if ($try) {
				$host = $try;
			}
		}
		return $host;
	}

	public static function isCLI()
	{
		//return isset($_SERVER['argc']);
		return php_sapi_name() == 'cli';
	}

	/**
	 * http://www.zen-cart.com/forum/showthread.php?t=164174
	 */
	public static function getRequestType()
	{
		$HTTPS = ifsetor($_SERVER['HTTPS'], getenv('HTTPS'));
		$HTTP_X_FORWARDED_HOST = ifsetor($_SERVER['HTTP_X_FORWARDED_HOST']);
		$HTTPS_SERVER = ifsetor($_SERVER['HTTPS_SERVER']);
		$HTTP_X_FORWARDED_SSL = ifsetor($_SERVER['HTTP_X_FORWARDED_SSL']);
		$HTTP_X_FORWARDED_PROTO = ifsetor($_SERVER['HTTP_X_FORWARDED_PROTO']);
		$HTTP_X_FORWARDED_BY = ifsetor($_SERVER['HTTP_X_FORWARDED_BY']);
		$HTTP_X_FORWARDED_SERVER = ifsetor($_SERVER['HTTP_X_FORWARDED_SERVER']);
		$request_type =
			((($HTTPS) && (strtolower($HTTPS) == 'on' || $HTTPS == '1'))) ||
			(($HTTP_X_FORWARDED_BY) && strpos(strtoupper($HTTP_X_FORWARDED_BY), 'SSL') !== false) ||
			(($HTTP_X_FORWARDED_HOST) && (strpos(strtoupper($HTTP_X_FORWARDED_HOST), 'SSL') !== false)) ||
			(($HTTP_X_FORWARDED_HOST && $HTTPS_SERVER) && (strpos(strtoupper($HTTP_X_FORWARDED_HOST), str_replace('https://', '', $HTTPS_SERVER)) !== false)) ||
			(isset($_SERVER['SCRIPT_URI']) && strtolower(substr($_SERVER['SCRIPT_URI'], 0, 6)) == 'https:') ||
			(($HTTP_X_FORWARDED_SSL) && ($HTTP_X_FORWARDED_SSL == '1' || strtolower($HTTP_X_FORWARDED_SSL) == 'on')) ||
			(($HTTP_X_FORWARDED_PROTO) && (strtolower($HTTP_X_FORWARDED_PROTO) == 'ssl' || strtolower($HTTP_X_FORWARDED_PROTO) == 'https')) ||
			(isset($_SERVER['HTTP_SSLSESSIONID']) && $_SERVER['HTTP_SSLSESSIONID'] != '') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ||
			ifsetor($_SERVER['FAKE_HTTPS'])
			|| (str_startsWith($HTTP_X_FORWARDED_SERVER, 'sslproxy'))    // BlueMix
				? 'https' : 'http';
		return $request_type;
	}

	public static function getPort()
	{
		$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])
			? $_SERVER['HTTP_X_FORWARDED_HOST']
			: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
		$host = trimExplode(':', $host);    // localhost:8081
		$port = $host[1];
		return $port;
	}

	public static function removeCookiesFromRequest()
	{
		if (false !== strpos(ini_get('variables_order'), 'C')) {
			//debug($_COOKIE, ini_get('variables_order'));
			foreach ($_COOKIE as $key => $_) {
				if (!isset($_GET[$key]) && !isset($_POST[$key])) {
					unset($_REQUEST[$key]);
				}
			}
		}
	}

	public static function isCURL()
	{
		$isCURL = str_contains(ifsetor($_SERVER['HTTP_USER_AGENT']), 'curl');
		return $isCURL;
	}

	/**
	 * http://stackoverflow.com/questions/190759/can-php-detect-if-its-run-from-a-cron-job-or-from-the-command-line
	 * @return bool
	 */
	public static function isCron()
	{
		return !self::isPHPUnit()
			&& self::isCLI()
			&& !isset($_SERVER['TERM'])
			&& !self::isWindows();
	}

	public static function isPHPUnit()
	{
		//debug($_SERVER); exit();
		$phpunit = defined('PHPUnit');
		$phar = !!ifsetor($_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR']);
		$loader = !!ifsetor($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER']);
		$phpStorm = basename($_SERVER['PHP_SELF']) == 'ide-phpunit.php';
		$phpStorm2 = basename($_SERVER['PHP_SELF']) == 'phpunit';
		return $phar || $loader || $phpStorm || $phpStorm2 || $phpunit;
	}

	/**
	 * http://stackoverflow.com/questions/738823/possible-values-for-php-os
	 * @return bool
	 */
	public static function isWindows()
	{
		//$os = isset($_SERVER['OS']) ? $_SERVER['OS'] : '';
		//return $os == 'Windows_NT';
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	public static function printDocumentRootDebug()
	{
		pre_print_r([
			'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
			'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
			'PHP_SELF' => $_SERVER['PHP_SELF'],
			'cwd' => getcwd(),
			'getDocumentRootByRequest' => self::getDocumentRootByRequest(),
			'getDocumentRootByDocRoot' => self::getDocumentRootByDocRoot(),
			'getDocumentRootByScript' => self::getDocumentRootByScript(),
			'getDocumentRootByIsDir' => self::getDocumentRootByIsDir(),
			'getDocumentRoot' => self::getDocumentRoot() . '',
		]);
	}

	/**
	 * @return mixed|string
	 * //~depidsvy/something
	 */
	private static function getDocumentRootByScript()
	{
		$script = $_SERVER['SCRIPT_FILENAME'];
		$pos = strpos($script, '/public_html');
		if ($pos !== false) {
			$docRoot = substr(dirname($script), $pos);
			$docRoot = str_replace('public_html', '~depidsvy', $docRoot);
			return $docRoot;
		} else {
			$docRoot = dirname($_SERVER['PHP_SELF']);
			return $docRoot;
		}
	}

	public static function getDocumentRootByIsDir()
	{
		$result = self::dir_of_file(
			self::firstExistingDir(
				ifsetor($_SERVER['REQUEST_URI'])
			)
		);
		return cap($result);
	}

	/**
	 * dirname('/53/') = '/' which is a problem
	 * @param $path
	 * @return string
	 */
	public static function dir_of_file($path)
	{
		if ($path[strlen($path) - 1] == '/') {
			return substr($path, 0, -1);
		} else {
			return dirname($path);
		}
	}

	public static function firstExistingDir($path)
	{
		$check = $_SERVER['DOCUMENT_ROOT'] . $path;
		//		error_log($check);
		if (is_dir($check)) {
			return cap(rtrim($path, '\\'), '/');
		} elseif ($path) {
			//echo $path, BR;
			return self::firstExistingDir(self::dir_of_file($path));
		} else {
			return '/';
		}
	}

	public static function isHTTPS()
	{
		return self::getRequestType() === 'https';
	}

	public function getRawPost()
	{
		if (defined('STDIN')) {
			$post = stream_get_contents(STDIN);
		} else {
			$post = file_get_contents('php://input');
		}
		return $post;
	}

}
