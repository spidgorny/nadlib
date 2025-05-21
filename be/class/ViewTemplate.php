<?php

namespace nadlib\be\class;

use UL;
use ListFilesIn;
use AutoLoad;

class ViewTemplate extends \AppControllerBE
{

	public function sidebar(): array
	{
		$folder = AutoLoad::getInstance()->getAppRoot()->appendString('template');
		$content[] = $folder . BR;
		$files = new ListFilesIn($folder);
		$ul = new UL($files->getArrayCopy());
		$content[] = $ul;
		return $content;
	}

	public function render()
	{
		$content[] = '';
		return $content;
	}

}
