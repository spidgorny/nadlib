<?php

class LocalLangSerialize extends LocalLang
{

	function __construct($forceLang = NULL, $filename = null)
	{
		parent::__construct($forceLang);
		$data = file_get_contents($filename);
		$this->ll = unserialize($data);
	}

	function saveMissingMessage($text)
	{
		// TODO: Implement saveMissingMessage() method.
	}

}
