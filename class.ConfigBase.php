<?php

class ConfigBase {
	/**
	 * del: Public to allow Request to know if there's an instance
	 * @var Config
	 */
	protected static $instance;

	public $db_server = '127.0.0.1';
	public $db_user = 'root';
	protected $db_password = 'root';
	public $db_database = '';

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
		'.',
		'Cache',
		'Controller',
		'CSS',
		'Data',
		'DB',
		'Debug',
		'HTML',
		'js',
		'HTMLForm',
		'HTTP',
		'LocalLang',
		'ORM',
		'SQL',
		'Time',
		'User',
		'be/class',
		'be/class/DB',
		'be/class/Info',
		'be/class/Test',
        'Queue',
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
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		$this->documentRoot = Request::getDocumentRoot();
		if (Request::isCLI()) {
			$this->appRoot = getcwd();
		} else {
			$this->appRoot = dirname($_SERVER['SCRIPT_FILENAME']).'/';
			$this->appRoot = str_replace('/kunden', '', $this->appRoot); // 1und1.de
		}

		//debug_print_backtrace();
		nodebug(array(
			'Config->documentRoot' => $this->documentRoot,
			'Config->appRoot' => $this->appRoot,
		));
		//debug_pre_print_backtrace();

		//$appRoot = dirname($_SERVER['SCRIPT_FILENAME']);
		//$appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $appRoot);

		//$this->appRoot = str_replace('vendor/spidgorny/nadlib/be', '', $this->appRoot);
		//d(__FILE__, $this->documentRoot, $this->appRoot, $_SERVER['SCRIPT_FILENAME']);

		//print_r(array(getcwd(), 'class/config.yaml', file_exists('class/config.yaml')));
		if (file_exists('class/config.yaml')) {
			require_once 'vendor/mustangostang/spyc/Spyc.php';
			$this->config = Spyc::YAMLLoad('class/config.yaml');
		}
		$this->mergeConfig($this);
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') echo __METHOD__.BR;
	}

	/**
	 * For compatibility with PHPUnit you need to call
	 * Config::getInstance()->postInit() manually
	 * @return Config
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Config();
			self::$instance->postInit();
		}
		return self::$instance;
	}

	/**
	 * Does heavy operations during bootstraping
	 * @return $this
	 */
	public function postInit() {
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') echo __METHOD__.BR;
		if ($this->db_database) {
			if (extension_loaded('mysqlnd')) {
				$this->db = new dbLayerPDO(
					$this->db_user,
					$this->db_password,
					'mysql',
					'',
					$this->db_server,
					$this->db_database
				);
				$this->db->perform('set names utf8');
			} else {
				$this->db = new MySQL(
					$this->db_database,
					$this->db_server,
					$this->db_user,
					$this->db_password);
			}
			$this->qb = new SQLBuilder($this->db);
		}

		// init user here as he needs to access Config::getInstance()
		$this->user = NULL;
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') echo __METHOD__.BR;
		return $this;
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

    /**
     * @return \SQLBuilder
     */
    public function getQb()
    {
        if(!isset($this->qb)) {
            $this->setQb(new SQLBuilder(Config::getInstance()->db));
        }

        return $this->qb;
    }

    /**
     * @param \SQLBuilder $qb
     */
    public function setQb($qb)
    {
        $this->qb = $qb;
    }

}
