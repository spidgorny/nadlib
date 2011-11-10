<?php

class ConfigBase {
	/**
	 *
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

	protected function __construct() {
		$this->db = $GLOBALS['i']->db;
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

	protected function postInit() {
		// init user here as he needs to access Config::getInstance()
	}

	public function prefixTable($a) {
		return $a;
	}

}
