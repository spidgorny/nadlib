<?php

/**
 * Usage:
 * $source = array(
 * 		array(	// row 1
 * 			'col1' => 'val1',
 * 			'col2' => 'val2',
 *		),
 * 		'row2' => array(
 * 			'col1' => 'val3',
 * 			'col2' => 'val4',
 *		),
 * );
 * $ap = new ArrayPlus($source);
 * $column = $ap->column('col2');
 *
 * $column = array(
 * 		'0' => 'val2',
 * 		'row2' => 'val4',
 * );
 *
 */

require_once('class.IteratorArrayAccess.php');

class ArrayPlus extends IteratorArrayAccess implements Countable {

	function __construct(array $a = array()) {
		$this->data = $a;
	}

	/**
	 * Static initializers can be chained in PHP
	 * @param array $data
	 * @return ArrayPlus
	 */
	static function create(array $data = array()) {
		$self = new self($data);
		return $self;
	}

	function column($col) {
		$return = array();
		foreach ($this->data as $key => $row) {
			$return[$key] = $row[$col];
		}
		$this->data = $return;
		return $this;
	}

	function column_coalesce($col1, $col2) {
		$return = array();
		foreach ($this->data as $key => $row) {
			$return[$key] = $row[$col1] ? $row[$col1] : $row[$col2];
		}
		return $return;
	}

	function column_assoc($key, $val) {
		$data = array();
		foreach ($this->data as $row) {
			$data[$row[$key]] = $row[$val];
		}
		$this->data = $data;
		return $this;
	}

	/**
	 * Modifies itself
	 * @param type $key
	 * @return ArrayPlus
	 */
	function IDalize($key = 'id') {
		$data = array();
		foreach ($this->data as $row) {
			$keyValue = $row[$key];
			if (!$keyValue) {
				debug($this->data, $key, $row);
				throw new Exception(__METHOD__.'#'.__LINE__);
			}
			$data[$keyValue] = $row;
		}
		$this->data = $data;
		return $this;
	}

	function append($value, $key = NULL) {
		if (!is_null($key)) {
			$this->data[$key] = $value;
		} else {
			$this->data[] = $value;
		}
		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param unknown_type $callback
	 * @return unknown
	 */
	function each($callback) {
		foreach ($this->data as $i => &$el) {
			//$el = $callback($el, $i);
			$el = call_user_func($callback, $el, $i);
		} unset($el);
		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param unknown_type $callback
	 * @return unknown
	 */
	function eachCollect($callback) {
		foreach ($this->data as $i => $el) {
			$plus = $callback($el, $i);
			$content .= $plus;
		}
		return $content;
	}

    function ksort() {
    	ksort($this->data);
    	return $this;
    }

    function count() {
	    //debug(__METHOD__, sizeof($this->data));
    	return sizeof($this->data);
    }

    function searchColumn($key, $val) {
    	foreach ($this->data as $row) {
    		if ($row[$key] == $val) {
    			return $row;
    		}
    	}
    }

    function setData(array $data) {
    	$this->data = $data;
    	return $this;
    }

	function getData() {
		return $this->data;
	}

    function getAssoc($key, $val) {
    	$ret = array();
    	foreach ($this->data as $row) {
    		$ret[$row[$key]] = $row[$val];
    	}
    	return $ret;
    }

	public function trim() {
		foreach ($this->data as &$value) {
			$value = trim($value);
		}
	}

	public function map($callback) {
		return array_map($callback, $this->data);
	}

	public function wrap($a, $b) {
		foreach ($this->data as &$value) {
			$value = $a.$value.$b;
		}
		return $this->data;
	}

}

function AP(array $a = array()) {
	return ArrayPlus::create($a);
}

class ArrayPlusReference extends ArrayPlus {

	function __construct(array &$a = array()) {
		$this->data =& $a;
	}

    static function create(array &$data = array()) {
    	$self = new self($data);
    	return $self;
    }

	function &getData() {
		return $this->data;
	}

}

function APR(array &$a = array()) {
	return ArrayPlusReference::create($a);
}
