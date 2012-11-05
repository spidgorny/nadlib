<?php

/**
 * Combines a Collection of elements from a database with an HTMLFormTable
 */
interface HTMLFormCollection {

	function setField($field);

	function setForm(HTMLFormTable $form);

	function setValue($value);

	function renderHTMLForm();

}
