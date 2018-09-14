<?php

class SQLNow extends AsIs
{

	public function __construct()
	{
		parent::__construct('');
	}

	public function __toString()
	{
		$map = array(
			'sqlite' => "datetime('now')",
			'mysql' => 'now()',
			'mysqli' => 'now()',
			'ms' => 'GetDate()',
			'postgresql' => 'now()',
			'pg' => 'now()',
		);
		$schema = $this->db->getScheme();
		$content = $map[$schema] ?: end($map);
		return $content;    // should not be quoted
	}

}
