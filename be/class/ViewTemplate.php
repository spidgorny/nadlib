<?php

class ViewTemplate extends AppController {

	function sidebar() {
		$folder = AutoLoad::getInstance()->appRoot.'template';
		$content[] = $folder.BR;
		$files = new ListFilesIn($folder);
		$ul = new UL($files->getArrayCopy());
		$content[] = $ul;
		return $content;
	}

	function render() {
		$content[] = '';
		return $content;
	}

}
