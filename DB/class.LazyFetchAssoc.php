<?php

class LazyFetchAssoc implements ArrayAccess {
	/**
	 * @var $db dbLayerPG
	 */
	protected $db;
	protected $data = array();		// cache

	function __construct($table) {
		$this->table = $table;
		$this->db = $GLOBALS['db'];
	}

    public function offsetSet($offset, $value) {
		throw new Exception('Read-only!');
    }

    public function offsetExists($offset) {
		$this->offsetGet($offset);
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        if (!isset($this->data[$offset])) {
			$this->data[$offset] = $this->fetch($offset);
		}
		return $this->data[$offset];
    }

	protected function fetch($id) {
		$row = $this->db->fetchSelectQuery($this->table, array('id' => $id));
		return $row;
	}

}