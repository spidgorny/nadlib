<?php

class IteratorArrayAccess extends ArrayIteratorPlus implements ArrayAccess {

	/** ArrayAccess **/

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
	 * @return self
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
/*
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
*/
}
