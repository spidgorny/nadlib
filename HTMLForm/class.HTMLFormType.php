<?php

/**
 * Just a base class to be used in checking instanceof.
 *
 */

abstract class HTMLFormType
{

	/**
	 * @var HTMLForm
	 */
	protected $form;

	public $field;
	public $fullname;
	public $value;

	function __construct()
	{
	}

	function setField($field)
	{
		$this->field = $field;
	}

	function setForm(HTMLFormTable $f)
	{
		$this->form = $f;
		$this->fullname = $this->form->getName($this->field, '', TRUE);
	}

	/**
	 * It's a string value which needs to be parsed into the minutes!!!
	 *
	 * @param unknown_type $value - 10:00-13:30
	 */
	function setValue($value)
	{
		$this->value = $value;
	}

	abstract function render();

}
