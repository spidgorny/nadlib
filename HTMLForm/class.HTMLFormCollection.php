<?php

interface HTMLFormCollection {

	function setField($field);

	function setForm(HTMLFormTable $form);

	function setValue($value);

	function renderHTMLForm();

}
