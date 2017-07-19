<?php

class dbLayerJSON extends dbLayerBase {

	var $filename;

	var $data = [];

	/**
	 * dbLayerJSON constructor.
	 * @param string $filename
	 */
	function __construct($filename)
	{
		$this->filename = $filename;
		$this->data = json_decode(
			file_get_contents($this->filename), true
		);
	}

}
