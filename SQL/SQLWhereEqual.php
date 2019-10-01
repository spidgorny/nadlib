<?php

/**
 * Base class in order to check instanceof SQLWherePart
 */

class SQLWhereEqual extends SQLWherePart
{

	/**
	 * $this->field is inherited
	 * @var
	 */
	protected $val;

	public function __construct($field, $val)
	{
		parent::__construct();
		$this->field = $field;
		$this->val = $val;
	}

	public function __toString()
	{
//		llog(__METHOD__, $this->field, strip_tags(gettype2($this->val)));
//		llog([
//			'is_numeric' => is_numeric($this->val),
//			'is_null' => is_null($this->val),
//			'is_numeric(field)' => is_numeric($this->field),
//		]);
		if (is_numeric($this->val)) {    // leading 0 leads to problems
			$field = $this->db->quoteKey($this->field);
			//$sql = "({$field} = ".$this->val."
			//OR {$field} = '".$this->val."')";
			$sql = "{$field} = '" . $this->val . "'";
		} elseif (is_null($this->val)) {
			$sql = $this->field . ' IS NULL';
		} elseif (is_numeric($this->field)) {
			$sql = $this->val . '';
		} else {
			$sql = '/* SWE */ ' . $this->getWhereItem($this->field, $this->val);
		}
		return $sql;
	}

	/**
	 * @param string $key
	 * @param string|object $val
	 * @return string
	 * @throws MustBeStringException
	 */
	public function getWhereItem($key, $val)
	{
		$set = array();
		$key = $this->db->quoteKey(trim($key));
//		llog(__METHOD__, $key, get_class($val));
		if ($val instanceof AsIsOp) {       // check subclass first
			$val->injectDB($this->db);
			$val->injectField($key);
			$set[] = $val;    // inject field
		} elseif ($val instanceof AsIs) {
			$val->injectDB($this->db);
			//$val->injectField($key); // not needed as it will repeat the field name
			$val->injectField(NULL);
			$set[] = $key . ' = ' . $val;
		} elseif ($val instanceof SQLBetween) {
			$val->injectField($key);
			$set[] = $val->toString($key);
		} elseif ($val instanceof SQLValue) {
			$set[] = "/* SQLValue */ $key = " . $val;    // = ?
		} elseif ($val instanceof SQLWherePart) {
			if (!is_numeric($key)) {
				$val->injectField($key);
			}
			$set[] = (string)$val;
		} elseif ($val instanceof SimpleXMLElement) {
			$set[] = $val->asXML();
			//} else if (is_object($val)) {	// what's that for? SQLWherePart has been taken care of
			//	$set[] = $val.'';
		} elseif ($val === NULL) {
			$set[] = "$key IS NULL";
		} elseif ($val === 'NOTNULL') {
			$set[] = "$key IS NOT NULL";
		} elseif (in_array($key{strlen($key) - 1}, array('>', '<'))
			|| in_array(substr($key, -2), array('!=', '<=', '>=', '<>'))) {
			list($key, $sign) = explode(' ', $key); // need to quote separately
			$key = $this->db->quoteKey($key);
			$set[] = "$key $sign '$val'";
		} elseif (is_bool($val)) {
			$set[] = ($val ? '' : 'NOT ') . $key;
		} elseif (is_numeric($key)) {        // KEY!!!
			$set[] = $val;
		} else {
			//debug_pre_print_backtrace();
			try {
				$val = $this->db->quoteSQL($val);
			} catch (MustBeStringException $e) {
				debug('MustBeStringException', __METHOD__, $key, $val);
				throw $e;
			}
			$set[] = "$key = $val";
		}
		return first($set);
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'field' => $this->field,
			'value' => is_object($this->val) ? get_class($this->val) : $this->val,
			'sql' => (string)$this,
		];
	}

	public function getParameter()
	{
		if (is_object($this->val) && method_exists($this->val, 'getParameter')) {
			return $this->val->getParameter();
		}
		return null;
	}

}
