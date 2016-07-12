<?php
/**
 * This class is a replacement for fetchAll - foreach combination and should be used to reduce the memory
 * requirements of the script. It's meant to mimic Iterator classes in PHP5, but doesn't inherit the interface
 * completely. (wrong!?)
 */
class DatabaseResultIteratorAssoc extends DatabaseResultIterator {

	function retrieveRow() {
		$row = $this->db->fetchAssoc($this->dbResultResource);
//		debug(__METHOD__, $row);
		return $row;
    }

}
