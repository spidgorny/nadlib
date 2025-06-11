<?php

use Composer\Autoload\ClassLoader;

class InitNADLIB
{

	public $useCookies = true;

	/**
	 * @var ?AutoLoad
	 */
	public $al;

	public $startTime;

	public $endTime;

	/**
	 * @var ClassLoader
	 */
	public $composer;

	/**
	 * @var bool set in the constructor, but DEVELOPMENT is set later
	 */
	public $development;

	public function __construct()
	{
		$this->startTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
		require_once __DIR__ . '/AutoLoad.php';
		require_once __DIR__ . '/../HTTP/Request.php';
		require_once __DIR__ . '/../Debug/TaylorProfiler.php';
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

		$this->development = Request::isWindows()
			|| ifsetor($_COOKIE['debug']) === ifsetor($_SERVER['HTTP_HOST'])
			|| ini_get('debug')
			|| getenv('NADLIB')
			|| getenv('DEVELOPMENT');
	}

	public function disableAutoload(): static
	{
		$this->al = null;
		return $this;
	}

	public function init(): static
	{
		// maybe InitNADLIB was loaded by composer autoload
		require_once __DIR__ . '/../init.php';
		//print_r($_SERVER);
		$this->setDefaults();
		$this->setErrorReporting();

		if ($this->al) {
			$this->al->useCookies = $this->useCookies;
			$this->al->postInit();
			AutoLoad::register();
			//debug($this->al->folders);
		}

		// leads to problems when there are multiple Config classes
		if (class_exists('Config', false)) {
			Config::getInstance();
		}

		$this->setCache();
		//ini_set('short_open_tag', 1);	// not working
		Request::removeCookiesFromRequest();
		$this->endTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
		return $this;
	}

	private function setDefaults(): void
	{
		//debug($_COOKIE);
		if (!defined('DEVELOPMENT')) {
			if (Request::isCLI()) {
				define('DEVELOPMENT', $this->development);
				//echo 'DEVELOPMENT: ', DEVELOPMENT, BR;
			} else {
				define('DEVELOPMENT', ifsetor($_COOKIE['debug']));
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
	private function setErrorReporting(): void
	{
		if (DEVELOPMENT) {
			$isCLI = Request::isCLI();
			if (headers_sent($file, $line) && $file && $line && !Request::isPHPUnit() && !$isCLI) {
				// debug() not loaded yet
				pre_print_r('Output has started', $file, $line);
			}

			@header('X-nadlib: DEVELOPMENT');
			@header('X-PHP-version: ' . PHP_VERSION);
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
			ini_set('display_errors', false);
		}
	}

	private function setCache(): void
	{
		if (DEVELOPMENT) {
			TaylorProfiler::getInstance(!ifsetor($_REQUEST['fast']));    // usually true
			/* @var TaylorProfiler $profiler */
			if (class_exists('Config', false) && !Request::isCLI()) {
				//print_r(Config::getInstance()->config['Config']);
				// set_time_limit() has been disabled for security reasons
				$timeLimit = Config::getInstance()->timeLimit ?? 5;
				@set_time_limit($timeLimit);    // small enough to notice if the site is having perf. problems
			}

			$_REQUEST['d'] = $_REQUEST['d'] ?? null;
			if (!Request::isCLI() && !headers_sent()) {
				header('Cache-Control: no-cache, no-store, max-age=0');
				header('Expires: -1');
			}
		} elseif (!Request::isCLI() && !headers_sent()) {
			header('Cache-Control: no-cache, no-store, max-age=0');
			header('Expires: -1');
		}
	}

	/**
	 * Autoloading done by composer only
	 */
	public function initWithComposer(): void
	{
		$this->setDefaults();
		$this->setErrorReporting();
		$this->setCache();
		Request::removeCookiesFromRequest();
		$this->endTime = microtime(true) - ifsetor($_SERVER['REQUEST_TIME_FLOAT']);
	}

	public function initWhoops(): void
	{
		$run = new Whoops\Run();
		$handler = new Whoops\Handler\PrettyPageHandler();
		$run->pushHandler($handler);
		$run->register();
	}

}
