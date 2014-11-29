<?php

class ViewTemplate extends AppController {

	function sidebar() {
		$files = new ListFilesIn('templates');
		$ul = new UL($files);
		$content[] = $ul;
		return $content;
	}

	function render() {
		$content[] = '';
		return $content;
	}

}
