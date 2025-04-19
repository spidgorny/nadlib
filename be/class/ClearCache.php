<?php

class ClearCache extends AppControllerBE
{

	public $dir = 'cache/';

	public function __construct()
	{
		parent::__construct();
		$mf = new MemcacheFile();
		$this->dir = $mf->folder;
	}

	public function render(): string
	{
		$content = $this->performAction($this->detectAction());
		$files = $this->getFiles();
		$content .= '<h1>Files in ' . $this->dir . ' (' . count($files) . ')</h1>';
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
		return $content . $s;
	}

	public function getFiles()
	{
		$ccs = new ClearCacheService();
		return $ccs->getFiles($this->dir);
	}

	public function sidebar()
	{
		return $this->getActionButton('Clear Cache', 'clearCache');
	}

	public function clearCacheAction(): void
	{
		$ccs = new ClearCacheService();
		$ccs->clearCacheIn($this->dir);
	}

}
