<?php

class ConfigBase {
	/**
	 *
	 * @var Config
	 */
	protected static $instance;

	public $server = '127.0.0.1';
	public $database = 'rechnung_plus';
	public $user = 'root';
	public $password = '';

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
		if (!self::$instance) self::$instance = new Config();
		return self::$instance;
	}

	public function prefixTable($a) {
		return $a;
	}

}
