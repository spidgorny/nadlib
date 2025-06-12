<?php

class OODBaseMM extends OODBase
{

	/** @var array */
	public $idList = [];

	/** @var array */
	public $idFields = [];

	public function initByRow(array $row): void
	{
		parent::initByRow($row);

		$this->idList = [];
		foreach ($this->idFields as $field) {
			$this->idList[$field] = $this->data[$field];
		}

		//} else if (igorw\get_in($this->data, array($this->idField))) {   // not ifsetor

	}
}
