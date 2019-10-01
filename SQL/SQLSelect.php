<?php

class SQLSelect
{

	public $db;

	protected $parts = array();

	public function __construct($parts)
	{
		if (is_array($parts)) {
			$this->parts = $parts;
		} else if ($parts) {
			$this->parts[] = $parts;
		} else {
			$this->parts[] = '*';
		}
	}

	public function injectDB(DBInterface $db)
	{
		$this->db = $db;
	}

	public function __toString()
	{
		return implode(', ', $this->parts);
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'parts' => $this->parts,
		];
	}

}
