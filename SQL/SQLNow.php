<?php

class SQLNow extends AsIs
{

	public function __construct()
	{
		parent::__construct('');
	}

	public function __toString(): string
	{
		$map = [
			'sqlite' => "datetime('now')",
			'mysql' => 'now()',
			'mysqli' => 'now()',
			'ms' => 'GetDate()',
			'postgresql' => 'now()',
			'pg' => 'now()',
			DBPlacebo::class . '://' => 'now()'
		];
		$schema = $this->db->getScheme();
		if (!isset($map[$schema])) {
			throw new RuntimeException('[' . $schema . '] is not supported by SQLNow', E_USER_ERROR);
		}

		return $map[$schema];
	}

}
