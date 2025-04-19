<?php

/**
 * Class LocalLangTest
 * It's reading data from the class/ll-en.json file and writes new messages back to it
 */
class LocalLangTest extends LocalLangDummy
{

	public function __construct($forceLang = null)
	{
		parent::__construct($forceLang);
		$file = file_get_contents('class/ll-' . $this->lang . '.json');
		$this->ll = json_decode($file, true, 512, JSON_THROW_ON_ERROR);
		//debug($file, $this->ll);
	}

	public function updateMessage(array $data): void
	{
		$this->ll[$data['code']] = $data['text'];
		file_put_contents('class/ll-' . $this->lang . '.json', json_encode($this->ll, JSON_THROW_ON_ERROR));
	}

}
