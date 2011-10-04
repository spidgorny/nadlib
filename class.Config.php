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
		$di = new DIContainer();
		$di->db = $this->ms;
		$this->qb = new SQLBuilder($di);
		$this->my = new MySQL($this->database, $this->server, $this->user, $this->password);
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
