<?php

class UploadedFile {

	var $name;
	var $type;
	var $tmp_name;
	var $error;
	var $size;
	var $mime;

	function __construct(array $data)
	{
		foreach ($data as $key => $val) {
			$this->$key = $val;
		}
		$u = new MIME();
		$this->mime = $u->get_mime_type($this->tmp_name);
	}

}
