<?php

/**
 * Just a base class to be used in checking instanceof.
 *
 */

abstract class HTMLFormType {

	/**
	 * @var HTMLForm
	 */
	protected $form;

	public $name;
	public $fullname;
	public $value;

	function __construct() {
	}

	function setName($name) {
		$this->name = $name;
	}

	function setForm(HTMLFormTable $f) {
		$this->form = $f;
		$this->fullname = $this->form->getName($this->name, '', TRUE);
	}

	/**
	 * It's a string value which needs to be parsed into the minutes!!!
	 *
	 * @param unknown_type $value - 10:00-13:30
	 */
	function setValue($value) {
		$this->value = $value;
	}

	abstract function render();

}
