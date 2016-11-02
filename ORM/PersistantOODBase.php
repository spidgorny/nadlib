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
	public $originalData;

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
		$ret = NULL;
		nodebug(array('insert before',
			$this->stateHash => $this->originalData,
			$this->getStateHash() => $this->data,
			$this->id
		));
		try {
			$ret = parent::insert($data);
		} catch (Exception $e) {
			//debug('LastInsertID() failed but it\'s OK');
		}
		//debug($this->db->lastQuery);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		nodebug(array('insert after',
			$this->stateHash =>$this->originalData,
			$this->getStateHash() => $this->data,
			$this->id,
		));
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
			nodebug(array(
				$this->stateHash => $this->originalData,
				$this->getStateHash() => $this->data,
				$this->table => $this->id,
			));
			$idDefined = is_array($this->id)
				? trim(implode('', $this->id))
				: $this->id;
			if ($idDefined) {
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
		nodebug(array(
			$this->stateHash => $this->originalData,
			$this->getStateHash() => $this->data,
			$this->table => $this->id,
			'action' => $action,
		));
		//debug('table: '.$this->table.' action: '.$action.' id: '.$this->id);
		return $action;
	}

	function findInDB(array $where, $orderByLimit = '') {
		$ret = parent::findInDB($where, $orderByLimit);
		$this->originalData = $this->data;
		$this->stateHash = $this->getStateHash();
		return $ret;
	}
}
