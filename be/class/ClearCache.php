<?php

class ClearCache extends AppControllerBE
{

	var $dir = 'cache/';

	function __construct()
	{
		parent::__construct();
		$mf = new MemcacheFile();
		$this->dir = $mf->folder;
	}

	function render()
	{
		$content = $this->performAction();
		$files = $this->getFiles();
		$content .= '<h1>Files in ' . $this->dir . ' (' . sizeof($files) . ')</h1>';
		$s = new slTable($files, '', [
			'filelink' => [
				'name' => 'file',
				'no_hsc' => true,
			],
			'size' => 'size',
			'date' => [
				'name' => 'date',
				'type' => 'date',
				'format' => 'Y-m-d H:i:s',
			],
		]);
		$content .= $s;
		return $content;
	}

	function getFiles()
	{
		$ccs = new ClearCacheService();
		return $ccs->getFiles($this->dir);
	}

	function sidebar()
	{
		return $this->getActionButton('Clear Cache', 'clear');
	}

	function clearAction()
	{
		$ccs = new ClearCacheService();
		$ccs->clearCacheIn($this->dir);
	}

}
