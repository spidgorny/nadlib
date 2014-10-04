<?php

class AppControllerBE extends AppController {

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

	/**
	 * Protect from unauthorized access
	 * @var bool
	 */
	static $public = false;	// must be false at all times!

	function __construct() {
		parent::__construct();
		if ((!$this->user || !$this->user->isAdmin()) && !self::$public) {
			throw new AccessDeniedException(__('Access denied to page %1', get_class($this)));
		}
		if (class_exists('Index')) {
			$this->index = Index::getInstance();
		}
		//debug($this->request->getAll());
		if ($this->request->getBool('forceDL')) {
			$this->forceCronjobDL = true;
		}
		if ($this->request->getBool('force')) {
			$this->forceCronjob = true;
		}
		$this->nadlibFromDocRoot = AutoLoad::getInstance()->nadlibFromDocRoot;
	}

	function log($a) {
		echo $a.'<br />'."\n";
	}

	public function getURL(array $params, $prefix = '?') {
		$url = parent::getURL($params, $this->nadlibFromDocRoot.'be/?');
		return $url;
	}

}
