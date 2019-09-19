<?php

class CompareAssoc
{

	public $keys;

	public $assoc1;

	public $assoc2;

	function __construct($assoc1, $assoc2)
	{
		$this->keys = array_keys($assoc1) + array_keys($assoc2);
		$this->assoc1 = $assoc1;
		$this->assoc2 = $assoc2;
	}

	function render()
	{
		$table = array();
		foreach ($this->keys as $key) {
			$table[] = array(
				'key' => $key,
				'assoc1' => $this->assoc1[$key],
				'assoc2' => $this->assoc2[$key],
				'same' => $this->assoc1[$key] == $this->assoc2[$key],
				'identical' => $this->assoc1[$key] === $this->assoc2[$key],
			);
		}
		return new slTable($table);
	}

}
