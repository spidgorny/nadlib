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
		} elseif (is_numeric($this->field)) {
			$sql = $this->val.'';
		} else {
			$sql = $this->getWhereItem($this->field, $this->val);
		}
		return $sql;
	}

	function getWhereItem($key, $val) {
		$set = array();
		$key = $this->db->quoteKey(trim($key));
//		debug($key);
		if (false) {

		} elseif ($val instanceof AsIsOp) {       // check subclass first
			$val->injectDB($this->db);
			$val->injectField($key);
			if (is_numeric($key)) {
				$set[] = $val;
			} else {
				$set[] = /*$key . ' ' .*/ $val;	// inject field
			}
		} elseif ($val instanceof AsIs) {
			$val->injectDB($this->db);
			//$val->injectField($key); // not needed as it will repeat the field name
			$val->injectField(NULL);
			$set[] = $key . ' = ' . $val;
		} elseif ($val instanceof SQLBetween) {
			$val->injectField($key);
			$set[] = $val->toString($key);
		} elseif ($val instanceof SQLWherePart) {
			if (!is_numeric($key)) {
				$val->injectField($key);
			}
			$set[] = $val->__toString();
		} elseif ($val instanceof SimpleXMLElement) {
			$set[] = $val->asXML();
			//} else if (is_object($val)) {	// what's that for? SQLWherePart has been taken care of
			//	$set[] = $val.'';
		} elseif (isset($where[$key.'.']) && ifsetor($where[$key.'.']['asis'])) {
			if (strpos($val, '###FIELD###') !== FALSE) {
				$val = str_replace('###FIELD###', $key, $val);
				$set[] = $val;
			} else {
				$set[] = '('.$key . ' ' . $val.')';	// for GloRe compatibility - may contain OR
			}
		} elseif ($val === NULL) {
			$set[] = "$key IS NULL";
		} elseif ($val === 'NOTNULL') {
			$set[] = "$key IS NOT NULL";
		} elseif (in_array($key{strlen($key)-1}, array('>', '<'))
			|| in_array(substr($key, -2), array('!=', '<=', '>=', '<>'))) {
			list($key, $sign) = explode(' ', $key); // need to quote separately
			$key = $this->db->quoteKey($key);
			$set[] = "$key $sign '$val'";
		} elseif (is_bool($val)) {
			$set[] = ($val ? "" : "NOT ") . $key;
		} elseif (is_numeric($key)) {		// KEY!!!
			$set[] = $val;
		} elseif (is_array($val) && ifsetor($where[$key.'.']['makeIN'])) {
			$set[] = $key." IN ('".implode("', '", $val)."')";
		} elseif (is_array($val) && ifsetor($where[$key.'.']['makeOR'])) {
			foreach ($val as &$row) {
				if (is_null($row)) {
					$row = $key .' IS NULL';
				} else {
					$row = $key . " = '" . $row . "'";
				}
			}
			$or = new SQLOr($val);
			$or->injectQB($this->db->getQb());
			$set[] = $or;
		} else {
			//debug_pre_print_backtrace();
			try {
				$val = $this->db->quoteSQL($val);
			} catch (MustBeStringException $e) {
				debug(__METHOD__, $key, $val);
				throw $e;
			}
			$set[] = "$key = $val";
		}
		return first($set);
	}

	function debug() {
		return $this->__toString();
	}

	function injectField($field) {
//		debug(__METHOD__, $field);
		parent::injectField($field);
	}

}
