<?php

use spidgorny\nadlib\Debug\Debug;

class NadlibIndex
{

	/**
	 * @var NadlibIndex
	 */
	public static $instance;

	/**
	 * @var Request
	 */
	protected $request;

	protected \DIContainer $dic;

	public function __construct()
	{
		$in = new InitNADLIB();
		$in->init();

		$this->dic = new DIContainer();
		$this->dic->index = static function ($c) {
			require_once __DIR__ . '/../be/class/IndexBE.php';
			return IndexBE::getInstance(true);
		};
		$this->dic->debug = static function ($c): Debug {
			return new Debug($c->index);
		};
		$this->dic->config = static function ($c) {
			return ConfigBE::getInstance();
		};
		$this->dic->autoload = static function ($c): \AutoLoad {
			return AutoLoad::getInstance();
		};

		if (!class_exists('Config')) {
			require_once __DIR__ . '/../be/class/ConfigBE.php';
//			class_alias('ConfigBE', 'Config');
		}

		if (!class_exists('AppController', false)) {
			if (!class_exists('AppController')) {
				//class_alias('Controller', 'AppController');
			}

//			class_alias('AppController', 'AppControllerME');
		}

//		$this->dic->config->defaultController = HomeBE::class;

		self::$instance = $this;
	}

	public function render(): string
	{
		if (Request::isCLI()) {
			$content[] = $this->cliMode();
		} else {
			chdir('be');
			//echo ($this->dic->autoload->nadlibFromCWD);
			//$this->dic->autoload->nadlibFromCWD = '../'.$this->dic->autoload->nadlibFromCWD;
			//echo ($this->dic->autoload->nadlibFromCWD);

			/** @var IndexBE $i */
			$i = $this->dic->index;
			//echo get_class($i), BR, class_exists('Index'), BR, get_class(Index::getInstance());
			$content[] = $i->render();
		}

		return $this->s($content);
	}

	public function cliMode(): array
	{
		$content[] = 'Nadlib CLI mode';
		$this->request->importCLIparams();
		if ($cmd = $this->request->getTrim('0')) {
			$cmdAction = $cmd . 'Action';
			if (method_exists($this, $cmdAction)) {
				$content[] = $this->$cmdAction();
			}
		} else {
			throw new InvalidArgumentException('"' . $cmd . '" is unknown');
		}

		return $content;
	}

	public function s($content): string
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	public function initAction(): string
	{
		return 'initAction';
	}

}
