<?php

class CompareAssoc
{

	var $keys;

	var $assoc1;

	var $assoc2;

	/**
	 * @var array[]
	 */
	public $table;

	function __construct($assoc1, $assoc2)
	{
		$this->keys = array_keys($assoc1) + array_keys($assoc2);
		$this->assoc1 = $assoc1;
		$this->assoc2 = $assoc2;
		$this->compare();
	}

	function compare()
	{
		foreach ($this->keys as $key) {
			$this->table[$key] = [
				'key'       => $key,
				'assoc1'    => $this->assoc1[$key],
				'assoc2'    => $this->assoc2[$key],
				'same'      => $this->assoc1[$key] == $this->assoc2[$key],
				'identical' => $this->assoc1[$key] === $this->assoc2[$key],
			];
		}
	}

	function render()
	{
		return new slTable($this->table);
	}

	function isDifferent()
	{
		return array_reduce($this->table, function ($acc, $row) {
			return $acc + $row['same'];
		}, 0);
	}

}
