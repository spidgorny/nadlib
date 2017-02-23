<?php

class CsvIteratorWithHeader extends CsvIterator {

	/**
	 * @var array
	 */
	var $columns;

	public function __construct($filename, $delimiter = ',', $convertUTF8 = false) {
		parent::__construct($filename, $delimiter);
		$this->doConvertToUTF8 = $convertUTF8;
		$this->columns = parent::current();	// first line
//		$this->current();	// this should not be next();
		$this->next();
		//print_r($this->columns);
	}

	public function current() {
		parent::current();
		//debug($this->columns, $this->currentElement);
		if ($this->currentElement) {
			$this->currentElement = array_combine($this->columns, $this->currentElement);
		}
		return $this->currentElement;
	}

	public function next() {
		$return = parent::next();
		//debug($this->columns, $this->currentElement);
		if ($return !== false && $this->currentElement) {
			if (sizeof($this->currentElement) == sizeof($this->columns)) {
				$this->currentElement = array_combine($this->columns, $this->currentElement);
				return $this->currentElement;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function rewind() {
		parent::rewind();
		$this->next();	// skip header row again
		$this->next();	// jump to the next row
//		debug(__METHOD__, $this->current());
	}

	function ftell() {
		return ftell($this->filePointer);
	}

}
