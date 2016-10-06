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
 * class ArrayPlus
 * Rules:
 * 1. Don't access $this->data, use $this as an array
 * 2. Don't access by reference - data is not updated.
 * http://stackoverflow.com/questions/8685186/arrayaccess-in-php-assigning-to-offset-by-reference
 * 3. Don't set $this->data = $new, use $this->setData($new)
 */

class ArrayPlus extends ArrayObject implements Countable {

	function __construct(array $array = array()) {
		parent::__construct($array);
		$this->setData($array);
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

	/**
	 * Returns an array of the elements in a specific column.
	 * @param $col
	 * @return static
	 */
	function column($col) {
		$return = array();
		foreach ((array)$this as $key => $row) {
			$return[$key] = ifsetor($row[$col]);
		}
		$ap = new ArrayPlus($return);
		return $ap;
	}

	function column_coalesce($col1, $col2) {
		$return = array();
		foreach ((array)$this as $key => $row) {
			$return[$key] = ifsetor($row[$col1]) ? $row[$col1] : $row[$col2];
		}
		return $return;
	}

	function pluck($key) {
		return $this->column($key);
	}

	function column_assoc($key, $val) {
		$data = array();
		foreach ((array)$this as $row) {
			$data[$row[$key]] = $row[$val];
		}
		$this->setData($data);
		return $this;
	}

	function keepColumns(array $keep) {
		$data = array();
		foreach ((array)$this as $i => $row) {
			$row = array_intersect_key($row, array_combine($keep, $keep));
			$data[$i] = $row;
		}
		$this->setData($data);
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
		foreach ($this as $row) {
			$keyValue = $row[$key];
			if (!$keyValue && !$allowMerge) {
				$error = __METHOD__.'#'.__LINE__.' You may need to specify $this->idField in your model.';
				debug(array(
					'error' => $error,
					'key' => $key,
					'row' => $row,
					'data' => $this->data,
				));
				throw new Exception($error);
			}
			$data[$keyValue] = $row;
		}
		$this->setData($data);
		return $this;
	}

	function append($value, $key = NULL) {
		if (!is_null($key)) {
			$this[$key] = $value;
		} else {
			$this[] = $value;
		}
		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param callable $callback
	 * @return static
	 */
	function each($callback) {
		foreach ($this as $i => $el) {
			//$el = $callback($el, $i);
			$el = call_user_func($callback, $el, $i);
			$this[$i] = $el;
		}
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
		foreach ($this as $i => $el) {
			$plus = $callback($el, $i);
			$content .= $plus;
		}
		return $content;
	}

    function ksort() {
    	ksort($this);
    	return $this;
    }

	/**
	 * Returns the first found row
	 * @param $key
	 * @param $val
	 * @return mixed
	 */
	function searchColumn($key, $val) {
    	foreach ($this as $row) {
    		if ($row[$key] == $val) {
    			return $row;
    		}
    	}
    }

    /**
     * Chainable
     *
     * @param array $data
     * @return static
     */
    function setData(array $data) {
	    $this->exchangeArray($data);
    	return $this;
    }

	/**
	 * @return array
	 */
	function getData() {
		return (array)$this;
	}

	function getAssoc($key, $val) {
		$ret = array();
		foreach ($this as $row) {
			$ret[$row[$key]] = $row[$val];
		}
		return $ret;
	}

	/**
	 * @return static
	 */
	public function trim() {
		foreach ($this as $i => $value) {
			$this[$i] = trim($value);
		}
		return $this;
	}

	/**
	 * @param $callback
	 * @return static
	 */
	public function map($callback) {
		$this->setData(array_map($callback, $this->getData()));
		return $this;
	}

	/**
	 * @param $a
	 * @param $b
	 * @return array
	 */
	public function wrap($a, $b) {
		foreach ($this as $i => $value) {
			$this[$i] = $a.$value.$b;
		}
		return $this->getData();
	}

	/**
	 * Not working as expected.
	 * PHP 4 doesn't have seek() function
	 * PHP 5 doesn't have prev() method
	 * @param $key
	 */
	function getPrevNext($key) {
		$row = $this->findInData(array('id' => $key));
		$row2 = $this[$key];	// works, but how to get next?
		# http://stackoverflow.com/questions/4792673/php-get-previous-array-element-knowing-current-array-key
		# http://www.php.net/manual/en/arrayiterator.seek.php
		$arrayobject = new ArrayObject($this);
		$iterator = $arrayobject->getIterator();

		if ($iterator->valid()) {
			$iterator->seek(array_search($key, array_keys((array) $this)));
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
		$keys = array_keys($this->getData());
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
		$keys = array_keys($this->getData());
		$found_index = array_search($key, $keys);
		if ($found_index === false || $key == end($keys))
			return false;
		return $keys[$found_index+1];
	}

	/**
	 * Searches inside Recursive tree
	 * @param $needle
	 * @return array|null
	 */
	function find($needle) {
		foreach ($this as $key => $val) {
			//debug($needle, $key, $val);
			if ($val instanceof Recursive) {
				$sub = new ArrayPlus($val->getChildren());
				$find = $sub->find($needle);
				//$find = $val->findPath($)
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

	function findAlternativeFromMenu($current) {
		foreach ($this->items as $key => $rec) {
			/** @var $rec Recursive */
			//$found = $rec->findPath($this->current);
			if ($rec instanceof Recursive) {
				$children = $rec->getChildren();
				$found = isset($children[current]) ? $children[$current] : NULL;
				//debug($children, $found, $key, $this->current);
				return $found;
			}
		}
		return NULL;
	}

	function first() {
		$var = $this->getData();
		reset($var);
		return current($var);
	}

	/**
	 * Bug. Only one element per sorted field is allowed.
	 * @param $column
	 * @return $this
	 */
	function sortBy($column) {
		$this->insertKeyAsColumn();
		// buggy
		//$this->IDalize($column, true);	// allow merge
		//$this->ksort();

		// correct
		$copy = clone $this;
		$sortCol = $copy->column($column)->getData();
		$aCopy = $this->getData();
		array_multisort($sortCol, $aCopy);		// Associative (string) keys will be maintained, but numeric keys will be re-indexed.
		$this->exchangeArray($aCopy);
		$this->extractKeyFromColumn();
		return $this;
	}

	/**
	 * Enters array key as ['__key__']
	 * @param string $keyColumnName
	 * @return $this
	 */
	function insertKeyAsColumn($keyColumnName = '__key__') {
		foreach ($this->getData() as $key => $_) {
			$this[$key][$keyColumnName] = $key;
		}
		return $this;
	}

	/**
	 * Extracts key from array as ['__key__']
	 */
	function extractKeyFromColumn() {
		$new = array();
		foreach ($this as $row) {
			$key = $row['__key__'];
			unset($row['__key__']);
			$new[$key] = $row;
		}
		$this->setData($new);
		return $this;
	}

	function transpose() {
		$out = array();
		foreach ($this as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$out[$subkey][$key] = $subvalue;
			}
		}
		$this->setData($out);
		return $this;
	}

	/**
	 * @param array $column
	 * @return static
	 */
	function unshift(array $column) {
		reset($column);
		foreach ($this as $i => $row) {
			$this[$i] = array(current($column)) + $row;
			next($column);
		}
		return $this;
	}

	function sum() {
		return array_sum($this->getData());
	}

	function min() {
		if ($this->count()) {
		return min($this->getData());
		} else {
			return NULL;
		}
	}

	function max() {
		if ($this->count()) {
		return max($this->getData());
		} else {
			return NULL;
		}
	}

	function avg() {
		$count = $this->count();
		if ($count != 0) {
			return $this->sum() / $count;
		}
	}

	/**
	 * Runs get_object_vars() recursively
	 * @param array $data
	 * @return ArrayPlus
	 */
	function object2array(array $data = NULL) {
		$this->setData($this->objectToArray($this));
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

	/**
	 *	$tree = AP(array('a' => array('b' => array('c' => 'd'))));
		$linear = $tree->linearize();
		return slTable::showAssoc($linear, true, true);
		== "c": "d"
	 * @param array $data
	 * @return array
	 */
	function linearize(array $data = NULL) {
		$data = $data ? $data : $this;
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

	function filter($callback = NULL) {
		if ($callback /*is_callable($callback)*/) {
			$new = array_filter($this->getData(), $callback);
		} else {
			$new = array_filter($this->getData());
		}
		$this->setData($new);
		return $this;
	}

	function implode($sep = "\n") {
		return implode($sep, $this->getData());
	}

	function typoscript($prefix = '') {
		$replace = array();
		foreach ($this as $key => $val) {
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

	function concat() {
		return implode('', $this->getData());
	}

	function count_if($k) {
		$count = 0;
		foreach ($this as $val) {
			if ($val[$k]) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Searches table for specific columns and counts where $k2 is true
	 * @param array $k1s
	 * @param       $k2
	 * @return int
	 */
	function count_if_sub(array $k1s, $k2) {
		$count = 0;
		foreach ($this as $val) {
			foreach ($val as $key2 => $val2) {
				if (in_array($key2, $k1s) && $val2[$k2]) {
					$count++;
					break;
				}
			}
		}
		return $count;
	}

	/**
	 * Chainable
	 *
	 * @param $i
	 * @param $val
	 * @return $this
	 */
	function set($i, $val) {
		$this->offsetSet($i, $val);
		return $this;
	}

	/**
	 * Chainable
	 *
	 * @param mixed $i
	 * @return static
	 */
	function un_set($i) {
		$this->offsetUnset($i);
		return $this;
	}

	function get($i, $subkey = NULL) {
		$element = $this->offsetGet($i);
		if ($subkey) {
			$element = $element[$subkey];
		}
		return $element;
	}

	function debug() {
		return array(
			'count' => $this->count(),
		);
	}

    /**
     * @param $oldKey
     * @param $newKey
     * @return array
     * @throws Exception
     */
    function replace_key($oldKey, $newKey) {
        $keys = array_keys((array) $this);
        if (false === $index = array_search($oldKey, $keys)) {
            throw new Exception(sprintf('Key "%s" does not exit', $oldKey));
        }
        $keys[$index] = $newKey;
        $this->exchangeArray(array_combine($keys, array_values((array) $this)));
    }

	/**
	 * @param array $ar2
	 * @return static
	 */
	function merge_recursive_overwrite($ar2) {
		foreach ($ar2 as $key2 => $val2) {
			if (isset($this[$key2]) && is_array($this[$key2])) {
				$tmp = AP($this[$key2]);
				$tmp->merge_recursive_overwrite($val2);
				$this[$key2] = $tmp->getData();
			} else {
				$this[$key2] = $val2;
			}
		}
		return $this;
	}

	/**
	 * 2D table => 3D table
	 * @param $groupBy
	 * @return $this
	 */
	public function groupBy($groupBy) {
		$new = array();
		foreach ($this->getData() as $line) {
			$key = $line[$groupBy];
			$new[$key][] = $line;
		}
		$this->setData($new);
		return $this;
	}

	function sumGroups($field) {
		$new = new ArrayPlus();
		foreach ($this->getData() as $key => $subtable) {
			$ap = ArrayPlus::create($subtable);
			$new[$key] = $ap->column($field)->sum();
		}
		return $new;
	}

	public function columnEmpty($string) {
		foreach ($this->getData() as $row) {
			if (ifsetor($row[$string])) return false;
		}
		return true;
	}

	public function columnSet($string) {
		foreach ($this->getData() as $row) {
			if (isset($row[$string])) return true;
		}
		return false;
	}

	public function findDelete($niceName) {
		$ar = $this->getData();
		$index = array_search($niceName, $ar);
		if ($index !== FALSE) {
			array_splice($ar, $index, 1);
			$this->setData($ar);
		}
	}

	public function getKeys() {
		return new self(array_keys($this->getData()));
	}

	public function replaceKeys(array $visibleFields) {
		foreach ($visibleFields as $key => $val) {
			$this->replace_key($key, $val);
		}
		return $this;
	}

	/**
	 * http://php.net/manual/en/function.array-splice.php#111204
	 * @param $input
	 * @param $offset       - key of the element to insert BEFORE(!)
	 * @param $length
	 * @param $replacement
	 */
	static function array_splice_assoc(&$input, $offset, $length, $replacement) {
		$replacement = (array) $replacement;
		$key_indices = array_flip(array_keys($input));
		if (isset($input[$offset]) && is_string($offset)) {
			$offset = $key_indices[$offset];
		}
		if (isset($input[$length]) && is_string($length)) {
			$length = $key_indices[$length] - $offset;
		}

		$input = array_slice($input, 0, $offset, TRUE)
			+ $replacement
			+ array_slice($input, $offset + $length, NULL, TRUE);
	}

	/**
	 * Used in HTTP protocol
	 * @param null $joinWith
	 * @return array|string
	 */
	public function getHeaders($joinWith = NULL) {
		$headers = array();
		foreach ($this as $key => $val) {
			$headers[] = $key.': '.$val;
		}
		if ($joinWith) {
			$headers = implode($joinWith, $headers);
		}
		return $headers;
	}

	/**
	 * If we store array of objects, we can retrieve a specific property of all objects
	 * @param $name
	 * @return ArrayPlus
	 */
	function getProperty($name) {
		$result = array();
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$name;
			}
		}
		return new ArrayPlus($result);
	}

	function call($method) {
		$result = array();
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$method();
			}
		}
		$this->setData($result);
		return $this;
	}

	function andAll() {
		$result = true;
		foreach ($this->getData() as $i => $object) {
			$result = $result && $object;
		}
		return $result;
	}

	function orAll() {
		$result = false;
		foreach ($this->getData() as $i => $object) {
			$result = $result || $object;
		}
		return $result;
	}

	public function toStringEach() {
		$new = array();
		foreach ($this->getData() as $i => $mixed) {
			$new[$i] = (string)$mixed;
		}
		return $new;
	}

	public function combineSelf() {
		return $this->setData(array_combine(
				$this->getData(),
				$this->getData()));
	}

	function stringArray() {
		$new = array();
		foreach ($this->getData() as $i => $mixed) {
			$new[$i] = MergedContent::mergeStringArrayRecursive($mixed);
		}
		$this->setData($new);
		return $this;
	}

	public function contains($string) {
		return in_array($string, $this->getData());
	}
	
	function convertTo($className) {
		foreach ($this as $key => $row) {
			if (method_exists($className, 'getInstance')) {
				$instance = $className::getInstance($row);
			} else {
				$instance = new $className($row);
			}
			$this[$key] = $instance;
		}
		return $this;
	}

	public function makeTable($newKey) {
		$copy = array();
		foreach ($this->getData() as $key => $row) {
			if (is_array($row)) {
				$copy[$key] = $row;
			} else {
				$copy[$key] = array(
					$newKey => $row,
				);
			}
		}
		$this->setData($copy);
		return $this;
	}

	function sortByValue() {
		$data = $this->getData();
		asort($data);
		$this->setData($data);
		return $this;
	}

	function sortByKey() {
		$data = $this->getData();
		ksort($data);
		$this->setData($data);
		return $this;
	}

	function reverse() {
		$this->setData(array_reverse($this->getData()));
		return $this;
	}

	function getSlice($from = 0, $length = NULL) {
		return array_slice($this->getData(), $from, $length, TRUE);
	}

	function addColumn($columnName, $callback) {
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			$this[$i][$columnName] = call_user_func($callback, $row, $i);
		}
		return $this;
	}
	
	function values() {
		$this->setData(array_values($this->getData()));
		return $this;
	}

	/**
	 * @param $ap ArrayPlus|array
	 * @return ArrayPlus
	 */
	function diff($ap) {
		$new = clone($this);
		$ap = $ap instanceof ArrayPlus ? $ap->getData() : $ap;
		$new->setData(array_diff($new->getData(), $ap));
		return $new;
	}

	/**
	 * Filter rows where $key = $value
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	function where($key, $value) {
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			if ($row[$key] != $value) {
				unset($copy[$i]);
			}
		}
		// no setData() here
		return new self($copy);
	}

	/**
	 * Filter rows where $key = $value
	 * @param $key
	 * @param $value
	 * @return $this
	 */
	function filterWhere($key, $value) {
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			if ($row[$key] != $value) {
				unset($copy[$i]);
			}
		}
		$this->setData($copy);
		return $this;
	}

	/**
	 * http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
	 * @param array $array
	 * @return bool
	 * @static because it's used in the constructor of VisibleColumns
	 */
	static function has_string_keys(array $array) {
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}

}

function AP($a = array()) {
	if ($a instanceof ArrayPlus) {
		return $a;
	} elseif (is_array($a)) {
		return ArrayPlus::create($a);
	} else {
		throw new InvalidArgumentException(__METHOD__.' accepts array');
	}
}

