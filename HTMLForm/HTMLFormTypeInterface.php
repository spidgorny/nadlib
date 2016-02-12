<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.01.2016
 * Time: 14:59
 *
 * It's currently identical to HTMLFormFieldInterface
 * but Field is bigger than Type.
 * Type knows how to render <input type="text">
 * But Field knows how to wrap it with <tr><td><label>
 */
interface HTMLFormTypeInterface {

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
