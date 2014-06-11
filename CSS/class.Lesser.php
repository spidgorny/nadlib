<?php

class Lesser extends AppController {

	public $layout = 'none';

	protected $output = 'cache/merge.css';

	/**
	 * No auth needed
	 * @var bool
	 */
	public static $public = true;

	function __construct() {
		if (!$_REQUEST['d']) {
			unset($_COOKIE['debug']);
		}
		parent::__construct();
		$this->output = Path::make(AutoLoad::getInstance()->appRoot)->appendString($this->output);
		$cacheDir = dirname($this->output);
		(array(
			'appRoot' => AutoLoad::getInstance()->appRoot,
			'output' => $this->output,
			'cacheDir' => $cacheDir,
			'file_exists()' => file_exists($cacheDir),
			'is_dir()' => is_dir($cacheDir),
			'is_writable()' => is_writable($cacheDir)
		));
		if (!is_dir($cacheDir)) {
			echo '#mkdir(', $cacheDir, ');'."\n";
			$ok = mkdir($cacheDir);
			if (!$ok) {
				throw new Exception('Cache dir not existing, can not be created. '.$cacheDir);
			}
		}
	}

	function render() {
		$less = new lessc();
		//$less->importDir[] = '../../';
		$cssFile = $this->request->getFilePathName('css');
		if ($cssFile) {
			$cssFileName = $this->request->getFilename('css');
			$this->output = dirname($this->output).'/'.str_replace('.less', '.css', $cssFileName);
			//debug($cssFile, $cssFileName, file_exists($cssFile), $this->output);

			if (!headers_sent()) {
				header("Date: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");;
				header("Expires: " . gmdate("D, d M Y H:i:s", time() + 60 * 60 * 24) . " GMT");
				header('Pragma: cache');
				header_remove('Cache-control');
			}

			if (is_writable($this->output)) {
				if ($this->request->isRefresh()) {
					$less->compileFile($cssFile, $this->output);
				} else {
					$less->checkedCompile($cssFile, $this->output);
				}

				if (file_exists($this->output)) {
					header('Content-type: text/css');
					readfile($this->output);
				} else {
					echo 'error {}';
				}
			} else {
				@header('Content-type: text/css');
				echo $less->compileFile($cssFile);
			}
		} else {
			echo getDebug($_REQUEST);
			echo 'error which file?';
		}
		$this->request->set('ajax', true);	// avoid any HTML
		//debug($this->request->isAjax());
	}

}
