<?php

class UploadedFile {

	var $name;
	var $type;
	var $tmp_name;
	var $error;
	var $size;

	function __construct(array $data)
	{
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
	}

}
