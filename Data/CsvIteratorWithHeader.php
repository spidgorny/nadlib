<?php

class CsvIteratorWithHeader extends CsvIterator {

	/**
	 * @var array
	 */
	var $columns;

	public function __construct($file, $delimiter = ',') {
		parent::__construct($file, $delimiter);
		parent::current();	// first line
		$this->next();
		$this->columns = $this->currentElement;
		//print_r($this->columns);
	}

	public function current() {
		parent::current();
		if ($this->currentElement) {
			$this->currentElement = array_combine($this->columns, $this->currentElement);
		}
		return $this->currentElement;
	}

}
