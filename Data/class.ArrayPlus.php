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
	 * @param string $key
	 * @param bool $allowMerge
	 * @throws Exception
	 * @return ArrayPlus
	 */
	function IDalize($key = 'id', $allowMerge = false) {
		$data = array();
		foreach ($this->data as $row) {
			$keyValue = $row[$key];
			if (!$keyValue && !$allowMerge) {
				debug($this->data, $key, $row);
				throw new Exception(__METHOD__.'#'.__LINE__.' You may need to specify $this->idField in your model.');
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
	 * @param callable $callback
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
	 * @param callable $callback
	 * @return string
	 */
	function eachCollect($callback) {
		$content = '';
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
		return $this;
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

	/**
	 * Not working as expected.
	 * PHP 4 doesn't have seek() function
	 * PHP 5 doesn't have prev() method
	 * @param $key
	 */
	function getPrevNext($key) {
		$row = $this->findInData(array('id' => $key));
		$row2 = $this->data[$key];	// works, but how to get next?
		# http://stackoverflow.com/questions/4792673/php-get-previous-array-element-knowing-current-array-key
		# http://www.php.net/manual/en/arrayiterator.seek.php
		$arrayobject = new ArrayObject($this->data);
		$iterator = $arrayobject->getIterator();

		if ($iterator->valid()) {
			$iterator->seek(array_search($key, array_keys($this->data)));
			$row3 = $iterator->current();
			$iterator->next();
			$next = $iterator->current();
			$iterator->previous();
			$iterator->previous();
			$prev = $iterator->current();
		}
		debug($key, $row, $row2, $row3['id'], $next['id'], $prev['id']); //, $rc->data);//, $rc);
	}

	/**
	 * http://stackoverflow.com/a/9944080/417153
	 * @param $key
	 * @return bool
	 */
	function getPrevKey($key) {
		$keys = array_keys($this->data);
		$found_index = array_search($key, $keys);
		if ($found_index === false || $found_index === 0)
			return false;
		return $keys[$found_index-1];
	}

	/**
	 * http://stackoverflow.com/a/9944080/417153
	 * @param $key
	 * @return bool
	 */
	function getNextKey($key) {
		$keys = array_keys($this->data);
		$found_index = array_search($key, $keys);
		if ($found_index === false || $key == end($keys))
			return false;
		return $keys[$found_index+1];
	}

	function find($needle) {
		foreach ($this->data as $key => $val) {
			//debug($needle, $key, $val);
			if ($val instanceof Recursive) {
				$sub = new ArrayPlus($val->getChildren());
				$find = $sub->find($needle);
				if ($find) {
					//debug($needle, $key, $find);
					array_unshift($find, $key);
					//debug($find);
				}
			} else {
				$find = ($key == $needle) ? array($key) : NULL;
			}
			if ($find) {
				return $find;
			}
		}
	}

	function first() {
		reset($this->data);
		return current($this->data);
	}

	function sortBy($column) {
		foreach ($this->data as $key => &$row) {
			$row['__key__'] = $key;
		}
		$this->IDalize($column, true);	// allow merge
		$this->ksort();

		$new = array();
		foreach ($this->data as $row) {
			$key = $row['__key__'];
			unset($row['__key__']);
			$new[$key] = $row;
		}
		$this->data = $new;
		return $this;
	}

	function transpose() {
		$out = array();
		foreach ($this->data as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$out[$subkey][$key] = $subvalue;
			}
		}
		$this->data = $out;
		return $this;
	}

	function unshift(array $column) {
		reset($column);
		foreach ($this->data as &$row) {
			$row = array(current($column)) + $row;
			next($column);
		}
		return $this;
	}

	function sum() {
		return array_sum($this->data);
	}

	/**
	 * Runs get_object_vars() recursively
	 * @param array $data
	 * @return ArrayPlus
	 */
	function object2array(array $data = NULL) {
		$this->data = $this->objectToArray($this->data);
		return $this;
	}

	/**
	 * http://www.if-not-true-then-false.com/2009/php-tip-convert-stdclass-object-to-multidimensional-array-and-convert-multidimensional-array-to-stdclass-object/
	 * @param $d
	 * @return array
	 */
	protected function objectToArray($d) {
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(array($this, __FUNCTION__), $d);
		}
		else {
			// Return array
			return $d;
		}
	}

	function linearize(array $data = NULL) {
		$data = $data ? $data : $this->data;
		$linear = array();
		foreach ($data as $key => $val) {
			if (is_array($val) && $val) {
				$linear = array_merge($linear, $this->linearize($val));
			} else {
				$linear[$key] = $val;
			}
		}
		return $linear;
	}

	function filter() {
		$this->data = array_filter($this->data);
		return $this;
	}

	function implode($sep) {
		return implode($sep, $this->data);
	}

	function typoscript($prefix = '') {
		$replace = array();
		foreach ($this->data as $key => $val) {
			$prefixKey = $prefix ? $prefix.'.'.$key : $key;
			if (is_array($val)) {
				$plus = AP($val)->typoscript($prefixKey);
				$replace += $plus;
			} else {
				$replace[$prefixKey] = $val;
			}
		}
		return $replace;
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
