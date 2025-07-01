<?php

/**
 * Just a base class to be used in checking instanceof.
 *
 */

abstract class HTMLFormType implements HTMLFormFieldInterface
{

	/**
	 * @var ?HTMLForm
	 */
	public $form;

	/**
	 * This is an array of a prefix + field name
	 * Use $this->form->getName() to convert to string
	 * @var array
	 */
	public $field = [];

	/**
	 * @var string like prefix[p2][asd]
	 */
	public $fullName;

	/**
	 * @var mixed
	 */
	public $value;

	/**
	 * @var HTMLFormField|array
	 */
	public $desc;

	/**
	 * Feel free to create any constructor you like
	 * HTMLFormType constructor.
	 */
	//	function __construct() {
	//	}
	/**
	 * @param $fieldName
	 */
	public function setField($fieldName): void
	{
		$this->field = $fieldName;
	}

	public function setForm(HTMLForm $form): void
	{
		$this->form = $form;
		$this->fullName = $this->form->getName($this->field, '', true);
	}

	/**
	 * @param string|int|array|null $value
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}

	public function __toString(): string
	{
		return MergedContent::mergeStringArrayRecursive($this->render()) . '';
	}

	/**
	 * Can't inherit abstract function HTMLFormFieldInterface::render() (previously declared abstract in HTMLFormType)
	 */
	public function render(): string|array|ToStringable
	{
		die(__METHOD__ . ' is abstract');
	}

	/**
	 * Return error message
	 */
	public function validate()
	{
		return null;
	}

	public function offsetExists(mixed $offset): bool
	{
		return $this->desc[$offset];
	}

	public function offsetGet(mixed $offset): mixed
	{
		return $this->desc[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->desc[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->desc[$offset]);
	}

}
