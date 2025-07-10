<?php

/**
 * @todo: move to DCI
 */
class TestConfig extends DCIConfig
{

	/** @var DBLayerDCI|DBPlacebo|null */
	protected $db;

	public function getDB()
	{
		if (!$this->db) {
			$this->db = new DBPlacebo();
			$this->db->qb = $this->getQB();
		}

		return $this->db;
	}

	public function getQB(): SQLBuilder
	{
		return new SQLBuilder($this->getDB());
	}

}
