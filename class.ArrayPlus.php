<?php

class ArrayPlus implements ArrayAccess {
	var $data = array();

	function __construct(array $array) {
		$this->data = $array;
	}

	function column($col) {
		$return = array();
		foreach ($this->data as $key => $row) {
			$return[$key] = $row[$col];
		}
		return $return;
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
		return $data;
	}

	/**
	 * Modifies itself
	 * @param type $key
	 * @return type
	 */
	function IDalize($key) {
		$data = array();
		foreach ($this->data as $row) {
			$data[$row[$key]] = $row;
		}
		$this->data = $data;
		return $this->data;
	}

	/**
	 * Static initializers can be chained in PHP
	 * @param array $a
	 * @return ArrayPlus
	 */
	static function create(array $a = array()) {
		return new self($a);
	}

	/** ArrayAccess **/

	function set($i, $val) {
		$this->data[$i] = $val;
	}

	/**
	 * Chainable
	 *
	 * @param unknown_type $i
	 * @return unknown
	 */
	function un_set($i) {
		unset($this->data[$i]);
		return $this;
	}

	function get($i, $subkey = NULL) {
		$element = $this->data[$i];
		if ($subkey) {
			$element = $element[$subkey];
		}
		return $element;
	}

	public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        return $this->un_set($offset);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
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
