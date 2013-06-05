<?php

namespace spidgorny\nadlib\Data;

class ListFilesIn extends \ArrayObject {

	function __construct($folder) {
		//parent::__construct();
		$menu = array();
		$iterator = new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
		//$iterator = new RecursiveIteratorIterator($iterator);
		foreach ($iterator as $file) { /** @var $file SplFileInfo */
			$filename = $file->getFilename();
			$key = $file->getPathname();
			//$key = str_replace($folder, '', $key);
			//$key = first(trimExplode('.', $key, 2));	// failed, will overwrite class.anything.php
			if ($file->isDir()) {
				$children = new self($folder.$filename);
				$menu[$key] = new Recursive($key, $children->getArrayCopy());
			} else {
				$menu[$key] = $file;
			}
		}
		//debug($folder, $menu);
		parent::__construct($menu);
	}

}