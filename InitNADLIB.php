<?php

class InitNADLIB
{

	public $useCookies = true;

	/**
	 * @var AutoLoad
	 */
	public $al;

	public $startTime;

	public $endTime;

	function __construct()
	{
		$this->startTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
		require_once __DIR__ . '/AutoLoad.php';
		require_once __DIR__ . '/HTTP/Request.php';
		require_once __DIR__ . '/Debug/TaylorProfiler.php';
		if (!defined('BR')) {
			if (Request::isCLI()) {
				define('BR', "\n");
			} else {
				define('BR', "<br />\n");
			}
		}
		if (!defined('TAB')) {
			define('TAB', "\t");
		}
		$this->al = AutoLoad::getInstance();
		$this->al->useCookies = $this->useCookies;
	}

	function init()
	{
		//print_r($_SERVER);
		$this->setDefaults();
		$this->setErrorReporting();

		if ($this->al) {
			$this->al->useCookies = $this->useCookies;
			$this->al->postInit();
			$this->al->register();
		}

		// leads to problems when there are multiple Config classes
		if (class_exists('Config', false)) {
			Config::getInstance();
		}

		$this->setCache();
		//ini_set('short_open_tag', 1);	// not working
		Request::removeCookiesFromRequest();

		$this->setupComposer();

		$this->endTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
	}

	function initWhoops()
	{
		$run = new Whoops\Run;
		$handler = new Whoops\Handler\PrettyPageHandler;
		$run->pushHandler($handler);
		$run->register();
	}

	private function setDefaults()
	{
//debug($_COOKIE);
		if (!defined('DEVELOPMENT')) {
			$isDebug = ifsetor($_COOKIE['debug'], getenv('DEBUG'));
			if (Request::isCLI()) {
				define('DEVELOPMENT', Request::isWindows() || $isDebug);
				echo 'DEVELOPMENT: ', DEVELOPMENT, BR;
			} else {
				define('DEVELOPMENT', $isDebug);
			}
		}

		date_default_timezone_set('Europe/Berlin');    // before using header()
		if (extension_loaded('mbstring')) {
			mb_internal_encoding('UTF-8');
		} else {
//			echo 'PHP: ', phpversion(), BR;
//			pre_print_r(get_loaded_extensions());
//			echo 'Ini file: ', php_ini_loaded_file(), BR;
//			phpinfo();
		}
		setlocale(LC_ALL, 'UTF-8');
	}

	/**
	 */
	private function setErrorReporting()
	{
		if (DEVELOPMENT) {
			$isCLI = Request::isCLI();
			if (headers_sent($file, $line) && $file && $line && !Request::isPHPUnit() && !$isCLI) {
				// debug() not loaded yet
				pre_print_r('Output has started', $file, $line);
			}
			@header('X-nadlib: DEVELOPMENT');
			error_reporting(-1);
			//ini_set('display_errors', FALSE);
			//trigger_error(str_repeat('*', 20));	// log file separator

			ini_set('display_errors', true);
			ini_set('html_errors', !$isCLI);
			// htaccess may not work
			$error_prepend_string = ini_get('error_prepend_string');
			if (!$error_prepend_string && !$isCLI) {
				ini_set('error_prepend_string', '<pre style="
white-space: pre-wrap;
color: deeppink;
background: lightyellow;
padding: 1em;
border-radius: 5px;">');
				ini_set('error_append_string', '</pre>');
			}
			ini_set('xdebug.file_link_format', 'phpstorm://open?file=%f&line=%l');
			if (false) {
				trigger_error('test');
				$file = __FILE__;
				$line = __LINE__;
				$link = 'idea://open?file=' . $file . '&line=' . $line;
				echo '<pre>Error in <a href="' . $link . '">' . $file . '#' . $line . '</a></pre>';
			}
		} else {
			@header('X-nadlib: PRODUCTION');
			error_reporting(0);
			ini_set('display_errors', FALSE);
		}
	}

	private function setCache()
	{
		if (DEVELOPMENT) {
			TaylorProfiler::getInstance(!ifsetor($_REQUEST['fast']));    // usually true
			/* @var $profiler TaylorProfiler */
			$_REQUEST['d'] = $_REQUEST['d'] ?? null;
			if (!Request::isCLI() && !headers_sent()) {
				header('Cache-Control: no-cache, no-store, max-age=0');
				header('Expires: -1');
			}
		} else {
			if (!Request::isCLI() && !headers_sent()) {
				header('Cache-Control: no-cache, no-store, max-age=0');
				header('Expires: -1');
			}
		}
	}

	private function setupComposer()
	{
// in DCI for example, we don't use composer (yet!?)
		$vendor_autoload_php = 'vendor/autoload.php';
		$vendor_autoload_php = realpath($vendor_autoload_php);
		// nadlib/vendor has files loaded from composer.json
		$standaloneNadlib = str_contains($vendor_autoload_php, 'nadlib\vendor');
		//echo 'SN: ', $standaloneNadlib, BR;
		//echo $vendor_autoload_php, ': ', file_exists($vendor_autoload_php), BR;
		if (!$standaloneNadlib
			&& file_exists($vendor_autoload_php)
		) {
			//echo $vendor_autoload_php, BR;
			/** @noinspection PhpIncludeInspection */
			require_once $vendor_autoload_php;
		}
	}

}
