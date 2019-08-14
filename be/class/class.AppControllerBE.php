<?php

class AppControllerBE extends Controller
{

	/**
	 * -forceDL in CLI will re-download and extract data
	 * @var bool
	 */
	var $forceCronjobDL = false;

	/**
	 * - force in CLI will force process data even if they were processed recently
	 * @var bool
	 */
	var $forceCronjob = false;

	var $nadlibFromDocRoot;

	function __construct()
	{
		parent::__construct();
		$this->layout = new Wrap('<div class="col-md-10">', '</div>' . "\n");
		$this->index = Index::getInstance();
		//debug($this->request->getAll());
		if ($this->request->getBool('forceDL')) {
			$this->forceCronjobDL = true;
		}
		if ($this->request->getBool('force')) {
			$this->forceCronjob = true;
		}
		$this->nadlibFromDocRoot = AutoLoad::getInstance()->nadlibFromDocRoot;
	}

	function log($a)
	{
		echo $a . '<br />' . "\n";
	}

	public function getURL(array $params, $prefix = '?')
	{
		$url = parent::getURL($params, $this->nadlibFromDocRoot . 'be/?');
		return $url;
	}

}
