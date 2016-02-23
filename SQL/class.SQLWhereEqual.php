<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWhereEqual extends SQLWherePart {

	/**
	 * $this->field is inherited
	 * @var
	 */
	protected $val;

	function __construct($field, $val) {
		parent::__construct();
		$this->field = $field;
		$this->val = $val;
	}

	function __toString() {
		if (is_numeric($this->val)) {	// leading 0 leads to problems
			$field = $this->db->quoteKey($this->field);
			//$sql = "({$field} = ".$this->val."
			//OR {$field} = '".$this->val."')";
			$sql = "{$field} = '".$this->val."'";
		} elseif (is_null($this->val)) {
			$sql = $this->field . ' IS NULL';
		} else {
			$sql = $this->field . ' = ' . $this->db->quoteSQL($this->val);
		}
		return $sql;
	}

	function debug() {
		return $this->__toString();
	}

	function injectField($field) {
//		debug(__METHOD__, $field);
		parent::injectField($field);
	}

}
