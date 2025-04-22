<?php

class HTMLSubmit extends HTMLFormField
{

	public $value;

	protected array $params;

	/** @var HTMLForm */
	public $form;

	public $field;

	public function __construct($value = '', array $params = [])
	{
		parent::__construct($params);
		$this->value = $value;
		$this->params = $params;
		$this->field = ifsetor($params['name'], 'btnSubmit');
	}

	/**
     * Shows the form element in the form
     */
    public function render(): string
	{
		$params = $this->params;
		$params['class'] = ifsetor($params['class'], 'submit btn');
		$params['name'] = ifsetor($params['name'], 'btnSubmit');
		//$value = htmlspecialchars(strip_tags($value), ENT_QUOTES);
		//$this->stdout .= "<input type=\"submit\" ".$this->getAttrHTML($params)." ".($value?'value="'.$value.'"':"") . " $more />\n";
		// this.form.submit() will not work
		//debug('submit', $params);
		$content = $this->form->getInput("submit", $this->field, $this->value, $params, $params['class']);
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
