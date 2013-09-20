<?php

class ValidatorCheck extends AppControllerBE {

	function render() {
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
			'min' => array(
				'label' => 'Min 10',
				'optional' => true,
				'min' => '10',
			),
		));
		$f->fill($_REQUEST);
		$f->validate();
		$f->showForm();
		$f->submit();
		$content = $f;
		return $content;
	}

}
