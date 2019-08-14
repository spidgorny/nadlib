<?php

class ValidatorCheckTest extends AppControllerBE
{

	function render()
	{
		$f = new HTMLFormTable(array(
			'obligatory' => array(
				'label' => 'Obligatory',
				'validate' => 'obligatory',
			),
			'mustBset' => array(
				'label' => 'Must be set',
				'optional' => true,
				'validate' => 'mustBset',
			),
			'email' => array(
				'label' => 'Email',
				'optional' => true,
				'validate' => 'email',
			),
			'password' => array(
				'label' => 'Password',
				'optional' => true,
				'validate' => 'password',
			),
			'min' => array(
				'label' => 'Min 10',
				'optional' => true,
				'min' => '10',
			),
			'max' => array(
				'label' => 'Max 10',
				'optional' => true,
				'max' => '10',
			),
			'minlen' => array(
				'label' => 'Minlen 10',
				'optional' => true,
				'minlen' => '10',
			),
			'maxlen' => array(
				'label' => 'Maxlen 10',
				'optional' => true,
				'maxlen' => '10',
			),
			'int' => array(
				'label' => 'Int',
				'optional' => true,
				'validate' => 'int',
			),
			'date' => array(
				'label' => 'Date',
				'optional' => true,
				'validate' => 'date',
			),
		));
		$f->fill($_REQUEST);
		unset($f->desc['mustBset']['value']);
		$f->validate();
		$f->showForm();
		$f->submit();
		$content = $f;
		return $content;
	}

}
