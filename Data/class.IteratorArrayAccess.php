<?php

namespace spidgorny\nadlib\Data;

class IteratorArrayAccess extends ArrayIteratorPlus implements \ArrayAccess {

	/** ArrayAccess **/

	function set($i, $val) {
		$this->data[$i] = $val;
		return $this;
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

}
