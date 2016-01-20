<?php

/**
 * Just a base class to be used in checking instanceof.
 *
 */

abstract class HTMLFormType implements HTMLFormFieldInterface {

	/**
	 * @var HTMLForm
	 */
	public $form;

	/**
	 * @var array
	 */
	public $field;

	public $fullName;

	public $value;

	/**
	 * @var array
	 */
	public $desc;

	function __construct() {
	}

	function setField($field) {
		$this->field = $field;
	}

	function setForm(HTMLForm $f) {
		$this->form = $f;
		$this->fullName = $this->form->getName($this->field, '', TRUE);
	}

	/**
	 * @param string $value
	 * @return mixed|void
	 */
	function setValue($value) {
		$this->value = $value;
	}

	abstract function render();

	function __toString() {
		return MergedContent::mergeStringArrayRecursive($this->render());
	}

	/**
	 * Return error message
	 * @return null
	 */
	function validate() {
		return NULL;
	}

}
