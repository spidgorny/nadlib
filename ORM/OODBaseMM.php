<?php

class OODBaseMM extends OODBase
{

	/// @var array
	public $id = [];

	/** @var array */
	public $idField = [];

	public function initByRow(array $row): void
	{
		parent::initByRow($row);

		$idField = $this->idField;

		$this->id = [];
		foreach ($idField as $field) {
			$this->id[$field] = $this->data[$field];
		}

		//} else if (igorw\get_in($this->data, array($this->idField))) {   // not ifsetor

	}
}
