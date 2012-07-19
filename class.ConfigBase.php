<?php

class ConfigBase {
	/**
	 * del: Public to allow Request to know if there's an instance
	 * @var Config
	 */
	protected static $instance;

	public $db_server = '127.0.0.1';
	public $db_database = 'rechnung_plus';
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
		'../nadlib/Data',
		'../nadlib/DB',
		'../nadlib/Debug',
		'../nadlib/HTML',
		'../nadlib/HTMLForm',
		'../nadlib/HTTP',
		'../nadlib/ORM',
		'../nadlib/SQL',
		'../nadlib/Time',
		'../nadlib/User',
		'../model',
	);

	protected function __construct() {
		$this->db = new MySQL($this->db_database, $this->db_server, $this->db_user, $this->db_password);
		$di = new DIContainer();
		$di->db = $this->db;
		$this->qb = new SQLBuilder($di);
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
	}

	public function prefixTable($a) {
		return $a;
	}

}
