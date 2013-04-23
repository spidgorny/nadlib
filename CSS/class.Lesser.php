<?php

class Lesser extends AppController {

	public $layout = 'none';

	protected $output = 'cache/merge.css';

	function render() {
		//unset($_COOKIE['debug']);
		$less = new lessc();
		//$less->importDir[] = '../../';
		$cssFile = $this->request->getFilePathName('css');
		if ($cssFile) {
			$this->output = 'cache/'.str_replace('.less', '.css', $this->request->getFilename('css'));
			//debug($cssFile, file_exists($cssFile), $this->output);
			$regen = $less->checkedCompile($cssFile, $this->output);
			if (file_exists($this->output)) {
				header('Content-type: text/css');
				readfile($this->output);
			} else {
				echo 'error {}';
			}
		} else {
			echo 'error which file?';
		}
		$this->request->set('ajax', true);	// avoid any HTML
		//debug($this->request->isAjax());
	}

}
