<?php

class Lesser extends AppController {

	public $layout = 'none';

	protected $output = 'css/merge.css';

	function render() {
		header('Content-type: text/css');
		$less = new lessc;
		//$less->importDir[] = '../../';
		$less->checkedCompile('css/'.$this->request->getFilename('css'), $this->output);
		readfile($this->output);
	}

}
