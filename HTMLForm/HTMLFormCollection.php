<?php

/**
 * Combines a Collection of elements from a database with an HTMLFormTable
 */
interface HTMLFormCollection {

	//var $field;
	/**
	 * @var HTMLForm
	 */
	//var $form;
	//var $value;

	function setField($field);

	function setForm(HTMLForm $form);

	function setValue($value);

	function renderHTMLForm();

	function setDesc($desc);

}
