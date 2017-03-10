<?php

class PersistantOODBase extends OODBase {

	/**
	 * @var string
	 */
	protected $stateHash;

	/**
	 *
	 * @var array
	 */
	protected $originalData;
/*	static public $inserted = 0;
	static public $updated = 0;
	static public $skipped = 0;
	// define them in a subclass for static::inserted to work
*/
	function __construct($initer) {
		parent::__construct($initer);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		//debug($this->getStateHash(), $this->stateHash, $this->data, $this->id);
	}

	function init($id, $fromFindInDB = false) {
		parent::init($id, $fromFindInDB);
	}

	function getStateHash() {
		return md5(serialize($this->data));
	}

	public function __set($property, $value) {
		$this->data[$property] = $value;
	}

	public function __get($property)  {
		if (isset($this->data[$property])) {
			return $this->data[$property];
		}
	}

	function __destruct() {
		//debug(get_called_class());
		$this->save();
	}

	/**
	 * Insert updates state hash so that destruct will not try to insert again
	 *
	 * @param array $data
	 * @return resource
	 */
	function insert(array $data) {
		$ret = parent::insert($data);
		//debug($this->db->lastQuery);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		return $ret;
	}

	/**
	 * Update updates state hash so that destruct will not try to update again
	 *
	 * @param array $data
	 * @return resource
	 */
	function update(array $data) {
		$ret = parent::update($data);
		//debug($this->db->lastQuery);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		return $ret;
	}

	function save() {
		if ($this->getStateHash() != $this->stateHash) {
			//debug($this->getStateHash(), $this->stateHash, $this->data, $this->originalData, $this->id);
			if ($this->id) {
				//debug(__CLASS__, $this->id, $this->getStateHash(), $this->stateHash, $this->data, $this->originalData);
				//debug(get_class($this), $this->id, $this->originalData, $this->data);
				$this->update($this->data);
				$action = 'UPDATE';
				static::$updated++;
			} else {
				$this->insert($this->data);
				$action = 'INSERT';
				static::$inserted++;
			}
		} else {
			$action = 'SKIP';
			static::$skipped++;
		}
		//debug('table: '.$this->table.' action: '.$action.' id: '.$this->id);
		return $action;
	}

}