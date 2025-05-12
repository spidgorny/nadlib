<?php

class HTMLInputWithOptions extends HTMLFormField
{

	public $field;

	/**
	 * @var HTMLForm
	 */
	public $form;

	public $value;

	protected array $options;

	protected $listName;

	public function __construct($field, $value, array $options, $listName = 'browsers')
	{
		$this->field = $field;
		$this->value = $value;
		$this->options = $options;
		$this->listName = $listName;
	}

	/**
     * Shows the form element in the form
     */
    public function render(): array
	{
		$content[] = '<input name="' . $this->form->getName($this->field) . '"
		list="' . $this->listName . '"
		value="' . htmlspecialchars($this->value) . '">
		<datalist id="' . $this->listName . '">';
		foreach ($this->options as $option => $description) {
			$content[] = '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($description) . '</option>';
		}

		$content[] = '</datalist>';
		return $content;
	}

	/**
     * Whet's the key name
     * @param $fieldName
     */
    public function setField($fieldName): void
	{
		$this->field = $fieldName;
	}

	/**
     * Inject form for additional function calls
     */
    public function setForm(HTMLForm $form): void
	{
		$this->form = $form;
	}

	/**
	 * Set current field value
	 * @param $value
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}
}
