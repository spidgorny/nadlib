<?php

class Lesser extends AppController
{

	public $layout = 'none';

	protected $output = 'cache/merge.css';

	function __construct()
	{
		if (!$_REQUEST['d']) {
			unset($_COOKIE['debug']);
		}
		parent::__construct();
	}

	function render()
	{
		$less = new lessc();
		//$less->importDir[] = '../../';
		$cssFile = $this->request->getFilePathName('css');
		if ($cssFile) {
			$cssFileName = $this->request->getFilename('css');
			$this->output = 'cache/' . str_replace('.less', '.css', $cssFileName);
			debug($cssFile, $cssFileName, file_exists($cssFile), $this->output);

			header("Date: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");;
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + 60 * 60 * 24) . " GMT");
			header('Pragma: cache');
			header_remove('Cache-control');
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
			echo 'error which file?';
		}
		$this->request->set('ajax', true);    // avoid any HTML
		//debug($this->request->isAjax());
	}

}
