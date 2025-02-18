<?php

class HTMLFormHidden extends HTMLFormType
{

	public function __construct($value)
	{
		$this->setValue($value);
	}

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	public function render()
	{
		$this->form->hidden($this->fullName, $this->value);
		$content[] = $this->form->getBuffer();
		return $content;
	}

}
