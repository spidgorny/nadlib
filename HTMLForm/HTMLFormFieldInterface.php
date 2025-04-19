<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 20.01.2016
 * Time: 14:59
 */
interface HTMLFormFieldInterface extends ArrayAccess
{

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	public function render();

	/**
	 * Whet's the key name
	 * @param $fieldName
	 * @return mixed
	 */
	public function setField($fieldName);

	/**
     * Inject form for additional function calls
     * @return mixed
     */
    public function setForm(HTMLForm $form);

	/**
	 * Set current field value
	 * @param $value
	 * @return mixed
	 */
	public function setValue($value);

}
