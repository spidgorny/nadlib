<?php

class ClearCache extends AppControllerBE {

	var $dir = 'cache/';

	function __construct() {
		parent::__construct();
		$mf = new MemcacheFile();
		$this->dir = $mf->folder;
	}

	function render() {
		$content = $this->performAction();
		$files = $this->getFiles();
		$content .= '<h1>Files in '.$this->dir.' ('.sizeof($files).')</h1>';
		$s = new slTable($files, '', array(
			'filelink' => array(
				'name' => 'file',
				'no_hsc' => true,
			),
			'size' => 'size',
			'date' => array(
				'name' => 'date',
				'type' => 'date',
				'format' => 'Y-m-d H:i:s',
			),
		));
		$content .= $s;
		return $content;
	}

	function getFiles() {
		$files = scandir($this->dir);
		//debug(sizeof($files));
		foreach ($files as $f => $file) {
			if ($file{0} != '.') {
				$files[$f] = array(
					'file' => $file,
					'filelink' => '<a href="../../../../cache/'.$file.'">'.$file.'</a>',
					'size' => filesize($this->dir.$file),
					'date' => filemtime($this->dir.$file),
				);
			} else {
				unset($files[$f]);
			}
		}
		return $files;
	}

	function sidebar() {
		return $this->getActionButton('Clear Cache', 'clear');
	}

	function clearAction() {
		$files = $this->getFiles();
		foreach ($files as $file) {
			$ext = pathinfo($this->dir.$file, PATHINFO_EXTENSION);
			if (in_array($ext, array('', 'cache'))) {
				unlink($this->dir.$file['file']);
				//echo $file, "\n";
				echo '.';
			}
		}
		echo "\n";
	}

}
