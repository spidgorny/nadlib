<?php

class TestConfig extends ConfigBase {

	function getDB() {
		if (!$this->db) {
			$this->db = new dbPlacebo();
			$this->db->qb = $this->getQb();
		}
		return $this->db;
	}

	function getQB() {
		return new SQLBuilder($this->getDB());
	}

}