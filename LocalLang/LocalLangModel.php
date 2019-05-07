<?php

class LocalLangModel extends OODBase
{
	var $table = 'app_interface';
	var $idField = 'id';

	function getValue()
	{
		return $this->data['text'];
	}

}
