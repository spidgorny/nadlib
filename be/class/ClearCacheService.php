<?php

class ClearCacheService
{

	function clearCacheIn($folder)
	{
		$files = $this->getFiles($folder);
		foreach ($files as $file) {
			$ext = pathinfo($folder . $file, PATHINFO_EXTENSION);
			if (in_array($ext, array('', 'cache'))) {
				unlink($folder . $file['file']);
				//echo $file, "\n";
				//echo '.';
			}
		}
		//echo "\n";
	}

	function getFiles($dir)
	{
		$files = scandir($dir);
		//debug(sizeof($files));
		foreach ($files as $f => $file) {
			if ($file{0} != '.') {
				$files[$f] = array(
					'file' => $file,
					'filelink' => '<a href="../../../../cache/' . $file . '">' . $file . '</a>',
					'size' => filesize($dir . $file),
					'date' => filemtime($dir . $file),
				);
			} else {
				unset($files[$f]);
			}
		}
		return $files;
	}

}
