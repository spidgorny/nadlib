<?php

/**
 * Class LazyFetchAssoc
 * Every time one needs to retrieve a record from a sub-table one can choose one of the ways below
 * Scenarios:
 * 1. Stupid. Run SELECT * FROM x WHERE id = <new id>. PHP 3.
 *      $projectRow = $this->db->fetchOneSelectQuery('project', array('id' => x));
 * 2. Preloading. Too greedy. Run SELECT * FROM x into the indexed array. PHP 4.
 * 		$this->projects = $GLOBALS['db']->rqfaid('project');
 * 3. Retrieve with invisible caching. This class.
		$this->projects = new LazyFetchAssoc('project');
 * 4. Combine this class with Memcache (not implemented)
 */
class LazyFetchAssoc implements ArrayAccess {

	/**
	 * @var $db dbLayerPG
	 */
	protected $db;

	/**
	 * cache
	 * @var array
	 */
	protected $data = array();

	public $idField = 'id';

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
		$row = $this->db->fetchSelectQuery($this->table, array($this->idField => $id));
		return $row;
	}

}