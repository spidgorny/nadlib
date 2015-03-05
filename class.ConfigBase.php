<?php

/**
 * Class ConfigBase - a Config, Singleton, Factory, Repository, DependencyInjectionContainer and Locator in one class.
 * Extend with a name Class and add any configuration parameters and factory calls.
 */
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

	/**
	 * @var Path
	 */
	public $documentRoot;

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

	/**
	 * Read from config.json
	 * @var array
	 */
	public $config;

	/**
	 * @var LoginUser
	 */
	public $user;

	/**
	 * @var string
	 * @deprecated
	 */
	public $appRoot;

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

		//print_r(array(getcwd(), 'class/config.json', file_exists('class/config.json')));
		$configYAML = AutoLoad::getInstance()->appRoot.'class/config.yaml';
		//print_r(array($configYAML, file_exists($configYAML)));
		if (file_exists($configYAML) && class_exists('Spyc')) {
			$this->config = Spyc::YAMLLoad($configYAML);
		}
		$this->mergeConfig($this);

		$configJSON = AutoLoad::getInstance()->appRoot.'class/config.json';
		if (file_exists($configJSON)) {
			$this->config = json_decode(file_get_contents($configJSON), true);
			$this->mergeConfig($this);
		}
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') echo __METHOD__.BR;
	}

	/**
	 * For compatibility with PHPUnit you need to call
	 * Config::getInstance()->postInit() manually
	 * @return Config - not ConfigBase
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Config();
			//self::$instance->postInit();	// will try to connect to the DB before autoload
			// must be called outside
		}
		return self::$instance;
	}

	/**
	 * Does heavy operations during bootstrapping
	 * @return $this
	 */
	public function postInit() {
		if ($this->db_database) {
			$di = new DIContainer();
			if (extension_loaded('mysqlnd')) {
				$di->db_class = 'dbLayerPDO';
				$this->db = new $di->db_class(
					$this->db_user,
					$this->db_password,
					'mysql',
					'',
					$this->db_server,
					$this->db_database
				);
				$this->db->perform('set names utf8');
			} else {
				$di->db_class = 'MySQL';
				$this->db = new $di->db_class(
					$this->db_database,
					$this->db_server,
					$this->db_user,
					$this->db_password);
			}
			$di->db = $this->db;
			$this->qb = new SQLBuilder($di);
			$this->db->qb = $this->qb;
		}

		// init user here as he needs to access Config::getInstance()
		$this->user = NULL;
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
		if (isset($this->config[$class]) && is_array($this->config[$class])) {
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
    public function getQb() {
        if (!isset($this->qb)) {
            $di = new DIContainer();
            $di->db = Config::getInstance()->db;
            $this->setQb(new SQLBuilder($di));
        }

        return $this->qb;
    }

    /**
     * @param \SQLBuilder $qb
     */
    public function setQb($qb) {
        $this->qb = $qb;
    }

}
