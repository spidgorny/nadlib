<?php

class LocalLangModel extends OODBase
{
	var $table = 'app_interface';
	var $idField = 'id';

	public function getValue()
	{
		return $this->data['text'];
	}

}
