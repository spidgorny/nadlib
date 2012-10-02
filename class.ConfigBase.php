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
	public $db_password = '';

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
		'../class',
		'../nadlib',
		'../nadlib/Cache',
		'../nadlib/Controller',
		'../nadlib/CSS',
		'../nadlib/Data',
		'../nadlib/DB',
		'../nadlib/Debug',
		'../nadlib/HTML',
		'../nadlib/HTMLForm',
		'../nadlib/HTTP',
		'../nadlib/LocalLang',
		'../nadlib/ORM',
		'../nadlib/SQL',
		'../nadlib/Time',
		'../nadlib/User',
		'../model',
	);

	/**
	 * Enables FlexiTable check if the all the necessary tables/columns exist.
	 * Disable for performance.
	 *
	 * @var bool
	 */
	public $flexiTable = false;

	public $config;

	protected function __construct() {
		if ($this->db_database) {
			$this->db = new MySQL($this->db_database, $this->db_server, $this->db_user, $this->db_password);
			$di = new DIContainer();
			$di->db = $this->db;
			$this->qb = new SQLBuilder($di);
		}
		$this->documentRoot = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
		if (file_exists('class/config.yaml')) {
			$this->config = Spyc::YAMLLoad('class/config.yaml');
		}
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
	 */
	function log($class, $message) {
		if (DEVELOPMENT) {
			throw new Exception($class.' '.$message);
		}
	}

}
