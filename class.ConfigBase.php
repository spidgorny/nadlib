<?php

class ConfigBase {
	/**
	 * del: Public to allow Request to know if there's an instance
	 * @var Config
	 */
	protected static $instance;

	public $db_server = '127.0.0.1';
	public $db_database = '';
	public $db_user = 'root';
	public $db_password = 'root';

	/**
	 * @var int
	 * @deprecated in favor of $this->config['Config']['timeLimit'] in init.php
	 */
	public $timeLimit = 10;

	/**
	 *
	 * @var SQLBuilder
	 */
	public $qb;

	/**
	 *
	 * @var MySQL
	 */
	public $db;

	public $defaultController = 'Overview';

	public $documentRoot = '';

	public static $includeFolders = array(
		'vendor/spidgorny/nadlib',
		'vendor/spidgorny/nadlib/Cache',
		'vendor/spidgorny/nadlib/Controller',
		'vendor/spidgorny/nadlib/CSS',
		'vendor/spidgorny/nadlib/Data',
		'vendor/spidgorny/nadlib/DB',
		'vendor/spidgorny/nadlib/Debug',
		'vendor/spidgorny/nadlib/HTML',
		'vendor/spidgorny/nadlib/HTMLForm',
		'vendor/spidgorny/nadlib/HTTP',
		'vendor/spidgorny/nadlib/LocalLang',
		'vendor/spidgorny/nadlib/ORM',
		'vendor/spidgorny/nadlib/SQL',
		'vendor/spidgorny/nadlib/Time',
		'vendor/spidgorny/nadlib/User',
		'class',	// to load the Config of the main project
		'model',
		'vendor/spidgorny/nadlib/be/class',
	);

	/**
	 * Enables FlexiTable check if the all the necessary tables/columns exist.
	 * Disable for performance.
	 *
	 * @var bool
	 */
	public $flexiTable = false;

	public $config;

	/**
	 * Default is that nadlib/ is in the root folder
	 * @var string
	 */
	public $appRoot;

	protected function __construct() {
		if ($this->db_database) {
			try {
				$this->db = new MySQL(
					$this->db_database,
					$this->db_server,
					$this->db_user,
					$this->db_password);
			} catch (Exception $e) {
				$this->db = new MySQL(
					$this->db_database,
					$this->db_server,
					$this->db_user,
					'');
			}
			$di = new DIContainer();
			$di->db = $this->db;
			$this->qb = new SQLBuilder($di);
		}

		$this->documentRoot = Request::getDocumentRoot();
		//$this->appRoot = dirname(__FILE__).'/..';
		$this->appRoot = dirname($_SERVER['SCRIPT_FILENAME']);
		//$this->appRoot = str_replace('vendor/spidgorny/nadlib/be', '', $this->appRoot);
		//debug(__FILE__, $this->appRoot);

		//print_r(array(getcwd(), 'class/config.yaml', file_exists('class/config.yaml')));
		if (file_exists('class/config.yaml')) {
			$this->config = Spyc::YAMLLoad('class/config.yaml');
		}
		$this->mergeConfig($this);
	}

	/**
	 *
	 * @return Config
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Config();
			self::$instance->postInit();
		}
		return self::$instance;
	}

	public function postInit() {
		// init user here as he needs to access Config::getInstance()
		$this->user = NULL;
	}

	public function prefixTable($a) {
		return $a;
	}

	/**
	 * TODO: enable FirePHP
	 * @param $class
	 * @param $message
	 * @throws Exception
	 */
	function log($class, $message) {
		if (DEVELOPMENT) {
			throw new Exception($class.' '.$message);
		}
	}

	function mergeConfig($obj) {
		$class = get_class($obj);
		if (is_array($this->config[$class])) {
			foreach ($this->config[$class] as $key => $val) {
				if ($key != 'includeFolders') {	// Strict Standards: Accessing static property Config::$includeFolders as non static
					$obj->$key = $val;
				}
			}
		}
	}

}
