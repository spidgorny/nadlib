<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.01.2016
 * Time: 14:59
 */
interface HTMLFormFieldInterface {

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	function render();

	/**
	 * Whet's the key name
	 * @param $field
	 * @return mixed
	 */
	function setField($field);

	/**
	 * Inject form for additional function calls
	 * @param HTMLForm $form
	 * @return mixed
	 */
	function setForm(HTMLForm $form);

	/**
	 * Set current field value
	 * @param $value
	 * @return mixed
	 */
	function setValue($value);

}
