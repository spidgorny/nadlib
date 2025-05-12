<?php

/**
 * Combines a Collection of elements from a database with an HTMLFormTable
 */
interface HTMLFormCollection
{

	public function setField($field);

	public function setForm(HTMLForm $form);

	public function setValue($value);

	public function renderHTMLForm();

	public function setDesc($desc);

}
