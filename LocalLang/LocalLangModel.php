<?php

class LocalLangModel extends OODBase
{
	public $table = 'app_interface';
	public $idField = 'id';

	function getValue()
	{
		return $this->data['text'];
	}

}
