<?php

class SQLSelect
{

	/**
	 * @var DBInterface
	 */
	public $db;

	public $parts = [];

	/**
	 * SQLSelect constructor.
	 *
	 * @param $parts array|string
	 */
	public function __construct($parts)
	{
		if (is_array($parts)) {
			$this->parts = $parts;
		} elseif ($parts) {
			$this->parts[] = $parts;
		} else {
			$this->parts[] = '*';
		}
	}

	public function injectDB(DBInterface $db): void
	{
		$this->db = $db;
	}

	public function __toString(): string
	{
		return implode(', ', $this->parts);
	}

	public function contains($string): bool
	{
		foreach ($this->parts as $p) {
			if (str_contains($p . '', $string)) {
				return true;
			}
		}

		return false;
	}

}
