<?php

class Config {
	/**
	 *
	 * @var Config
	 */
	protected static $instance;

	public $server = 'localhost';
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

	protected function __construct() {
		$this->db = new MySQL($this->database, $this->server, $this->user, $this->password);
		$di = new DIContainer();
		$di->db = $this->db;
		$this->qb = new SQLBuilder($di);
	}

	/**
	 *
	 * @return Config
	 */
	public static function getInstance() {
		if (!self::$instance) self::$instance = new self();
		return self::$instance;
	}

	public function prefixTable($a) {
		return $a;
	}

}
