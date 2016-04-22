<?php

class InitNADLIB {

	var $useCookies = true;

	/**
	 * @var AutoLoad
	 */
	var $al;

	var $startTime;

	var $endTime;

	function __construct() {
		$this->startTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
		require_once dirname(__FILE__) . '/class.AutoLoad.php';
		require_once dirname(__FILE__) . '/HTTP/class.Request.php';
		if (!defined('BR')) {
			if (Request::isCLI()) {
				define('BR', "\n");
			} else {
				define('BR', "<br />\n");
			}
		}
		$this->al = AutoLoad::getInstance();
	}

	function init() {
		//print_r($_SERVER);

		//debug($_COOKIE);
		if (!defined('DEVELOPMENT')) {
			if (Request::isCLI()) {
				define('DEVELOPMENT',
					 Request::isWindows()
					 || ifsetor($_COOKIE['debug'])
				 );
				echo 'DEVELOPMENT: ', DEVELOPMENT, BR;
			} else {
				define('DEVELOPMENT', ifsetor($_COOKIE['debug']));
			}
		}

		date_default_timezone_set('Europe/Berlin');	// before using header()
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding('UTF-8');
		}
		setlocale(LC_ALL, 'UTF-8');

		if (DEVELOPMENT) {
			if (headers_sent($file, $line) && $file && $line && !Request::isPHPUnit() && !Request::isCLI()) {
				debug('Output has started', $file, $line);
			}
			@header('X-nadlib: DEVELOPMENT');
			error_reporting(-1);
			//ini_set('display_errors', FALSE);
			//trigger_error(str_repeat('*', 20));	// log file separator

			ini_set('display_errors', TRUE);
			ini_set('html_error', TRUE);
			// htaccess may not work
			$error_prepend_string = ini_get('error_prepend_string');
			if (!$error_prepend_string && !Request::isCLI()) {
				ini_set('error_prepend_string', '<pre style="
white-space: pre-wrap;
color: deeppink;
background: lightyellow;
padding: 1em;
border-radius: 5px;">');
				ini_set('error_append_string', '</pre>');
				ini_set('html_errors', false);
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

		// don't use nadlib autoloading is using composer
		if ($this->al) {
			$this->al->useCookies = $this->useCookies;
			$this->al->postInit();
			$this->al->register();
		}

		// leads to problems when there are multiple Config classes
		if (class_exists('Config', false)) {
			Config::getInstance();
		}

		if (DEVELOPMENT) {
			TaylorProfiler::getInstance(!ifsetor($_REQUEST['fast']));	// usually true
			/* @var $profiler TaylorProfiler */
			if (class_exists('Config', false) && !Request::isCLI()) {
				//print_r(Config::getInstance()->config['Config']);
				// set_time_limit() has been disabled for security reasons
				@set_time_limit(Config::getInstance()->timeLimit
					? Config::getInstance()->timeLimit
					: 5);	// small enough to notice if the site is having perf. problems
			}
			$_REQUEST['d'] = isset($_REQUEST['d']) ? $_REQUEST['d'] : NULL;
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
		//ini_set('short_open_tag', 1);	// not working
		Request::removeCookiesFromRequest();

		// in DCI for example, we don't use composer (yet!?)
		$vendor_autoload_php = 'vendor/autoload.php';
		$vendor_autoload_php = realpath($vendor_autoload_php);
		// nadlib/vendor has files loaded from composer.json
		$standaloneNadlib = str_contains($vendor_autoload_php, 'nadlib\vendor');
		//echo 'SN: ', $standaloneNadlib, BR;
		//echo $vendor_autoload_php, ': ', file_exists($vendor_autoload_php), BR;
		if (!$standaloneNadlib
			&& file_exists($vendor_autoload_php)) {
			//echo $vendor_autoload_php, BR;
			/** @noinspection PhpIncludeInspection */
			require_once $vendor_autoload_php;
		}

		$this->endTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
	}

	function initWhoops() {
		$run     = new Whoops\Run;
		$handler = new Whoops\Handler\PrettyPageHandler;
		$run->pushHandler($handler);
		$run->register();
	}

}
