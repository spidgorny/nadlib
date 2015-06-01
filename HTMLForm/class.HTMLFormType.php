<?php

/**
 * Just a base class to be used in checking instanceof.
 *
 */

abstract class HTMLFormType {

	/**
	 * @var HTMLForm
	 */
	public $form;

	/**
	 * @var array
	 */
	public $field;

	public $fullname;

	public $value;

	function __construct() {
	}

	function setField($field) {
		$this->field = $field;
	}

	function setForm(HTMLForm $f) {
		$this->form = $f;
		$this->fullname = $this->form->getName($this->field, '', TRUE);
	}

	/**
	 * @param string $value
	 */
	function setValue($value) {
		$this->value = $value;
	}

	abstract function render();

	function __toString() {
		return $this->render().'';
	}

	/**
	 * Return error message
	 * @return null
	 */
	function validate() {
		return NULL;
	}

}
