<?php

class Config {
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

	protected function __construct() {
		if (LIVE) {
			$this->server = 'db1039.1und1.de';
			$this->database = 'db211772540';
			$this->user = 'dbo211772540';
			$this->password = 'wuwqaeR5';
		}
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
