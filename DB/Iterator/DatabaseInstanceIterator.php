<?php

class DatabaseInstanceIterator extends DatabaseResultIteratorAssoc
{

	var $className;

	function __construct(dbLayerBase $db, $className)
	{
		parent::__construct($db);
		$this->className = $className;
	}

	function retrieveRow()
	{
		$row = parent::retrieveRow();
		if ($row) {
			//debug($row, $this->className);
			if (method_exists($this->className, 'getInstance')) {
				$obj = call_user_func(
					array($this->className, 'getInstance'), $row);
			} else {
				$obj = new $this->className($row);
			}
			return $obj;
		}
		return FALSE;    // @see isValid()
	}

}
