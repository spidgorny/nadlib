<?php

/**
 * @property mixed $prefix
 * @property string $label
 * @property string $value
 * @property string $type
 * @property string $placeholder
 */
class HTMLFormInput extends HTMLFormField
{

	public $prefix;

	public $label;

	public $name;

	public $placeholder;

	public $value;

	public $type = 'text';

	/**
     * @var mixed[]
     */
    public $attr = [];

	public function __construct($name, array $attr = ['type' => 'text'])
	{
		parent::__construct($attr + ['type' => 'text'], $name);
//		$this->name = $name;
//		$this->assign($attr);
		$this->setValue($this->value);
		$this->name = $name;
		$this->attr = $attr;
	}

	/**
     * @deprecated
     */
    public function assign(array $attr): void
	{
		array_walk($attr, function ($value, $key): void {
			/** @noinspection PhpVariableVariableInspection */
			$this->$key = $value;
		});
	}

	public function __toString(): string
	{
		return MergedContent::mergeStringArrayRecursive($this->render()) . '';
	}

	public function render(): string|array
	{
		$name = $this->form->getName($this->field, '', true);
		llog($this->name, $this->form->getPrefix(), $name);
		$input = new HTMLTag('input', [
			'type' => $this->type,
			'name' => $name,
			'placeholder' => $this->placeholder,
			'value' => $this->value,
		]);
		return MergedContent::mergeStringArrayRecursive($input);
	}

	public function getContent(): string
	{
		return $this->render();
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->$offset) || isset($this->attr[$offset]);
	}

	public function &offsetGet(mixed $offset): mixed
	{
		if (isset($this->$offset)) {
			return $this->$offset;
		}

		return $this->attr[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (isset($this->$offset)) {
			$this->$offset = $value;
			return;
		}

		$this->attr[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		if (isset($this->$offset)) {
			unset($this->$offset);
			return;
		}

		unset($this->attr[$offset]);
	}
}
