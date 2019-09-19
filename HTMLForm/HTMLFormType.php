<?php

/**
 * Just a base class to be used in checking instanceof.
 *
 */

abstract class HTMLFormType implements HTMLFormFieldInterface
{

	/**
	 * @var HTMLForm
	 */
	public $form;

	/**
	 * @var array
	 */
	public $field;

	/**
	 * @var string like prefix[p2][asd]
	 */
	public $fullName;

	/**
	 * @var mixed
	 */
	public $value;

	/**
	 * @var array
	 */
	public $desc;

	/**
	 * Feel free to create any constructor you like
	 * HTMLFormType constructor.
	 */
//	function __construct() {
//	}

	/**
	 * @param $field
	 * @return mixed|void
	 */
	function setField($field)
	{
		$this->field = $field;
	}

	function setForm(HTMLForm $f)
	{
		$this->form = $f;
		$this->fullName = $this->form->getName($this->field, '', TRUE);
	}

	/**
	 * @param string $value
	 * @return mixed|void
	 */
	function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Can't inherit abstract function HTMLFormFieldInterface::render() (previously declared abstract in HTMLFormType)
	 */
	function render()
	{
		die(__METHOD__ . ' is abstract');
	}

	function __toString()
	{
		return MergedContent::mergeStringArrayRecursive($this->render()) . '';
	}

	/**
	 * Return error message
	 * @return null
	 */
	function validate()
	{
		return NULL;
	}

}
