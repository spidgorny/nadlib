<?php

class AppRootDetector {

	protected $appRoot;

	var $debug = false;

	/**
	 * Original idea was to remove vendor/s/nadlib/be from the CWD
	 * but since $this->nadlibRoot is relative "../" it's impossible.
	 * Now we go back one folder until we find "class/class.Config.php" which MUST exist
	 *
	 * Since it's not 100% that it exists we just take the REQUEST_URL
	 */
	function __construct()
	{
		if (Request::isCLI()) {
			$appRoot = dirname(dirname(getcwd()));
			return new Path(cap($appRoot));
		} elseif (Request::isPHPUnit()) {
			$appRoot = getcwd();
		} else {
			$appRoot = dirname(URL::getScriptWithPath());
			$appRoot = str_replace('/kunden', '', $appRoot); // 1und1.de
		}
		$appRoot = realpath($appRoot);
		//debug('$this->appRoot', $appRoot, $this->nadlibRoot);
		//$this->appRoot = str_replace('/'.$this->nadlibRoot.'be', '', $this->appRoot);
		while ($appRoot && ($appRoot != '/' && $appRoot != '\\')
			&& !($appRoot{1} == ':' && strlen($appRoot) == 3)	// u:\
		) {
			$config1 = $appRoot . DIRECTORY_SEPARATOR . 'index.php';
			$exists1 = file_exists($config1);
			if ($this->debug) {
				echo __METHOD__, ' ', $config1, ': ', $exists1, BR;
			}
			//debug($appRoot, strlen($appRoot), $exists);
			if ($exists1) {
				break;
			}
			$appRoot = dirname($appRoot);
		}

		if (!$appRoot || $appRoot == '/') {  // nothing is found by previous method
			if ($this->debug) {
				echo __METHOD__, ' Alternative way of app root detection', BR;
			}
			$appRoot = new Path(realpath(dirname(URL::getScriptWithPath())));
			//debug($appRoot, URL::getScriptWithPath());
			$appRoot->upIf('nadlib');
			$appRoot->upIf('spidgorny');
			$appRoot->upIf('vendor');
			$hasIndex = $appRoot->hasFile('index.php');
			//pre_print_r($appRoot.'', $hasIndex);
			if (!$hasIndex) {
				$appRoot->up();
			}
		}

		if ($this->debug) {
			echo __METHOD__, ' ', $appRoot, BR;
		}
		// always add trailing slash!
		$appRoot = cap($appRoot, '/');
		$appRoot = new Path($appRoot);
		$this->appRoot = $appRoot;
	}

	function get() {
		return $this->appRoot;
	}

}
