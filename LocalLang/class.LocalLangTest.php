<?php

class LocalLangTest extends LocalLangDummy {

	function __construct() {
		parent::__construct();
		$file = file_get_contents('class/ll-'.$this->lang.'.json');
		$this->ll = json_decode($file, true);
		//debug($file, $this->ll);
	}

	function updateMessage(array $data) {
		$this->ll[$data['code']] = $data['text'];
		file_put_contents('class/ll-'.$this->lang.'.json', json_encode($this->ll));
	}

}
