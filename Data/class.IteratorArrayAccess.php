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

	public function offsetSet($offset, $value) {
        parent::offsetSet($offset, $value);
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset) {
        parent::offsetUnset($offset);
        unset($this->data[$offset]);
    }

}
