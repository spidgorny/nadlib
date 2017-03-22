<?php

class HTMLFormLocation extends HTMLFormType {

	/**
	 * @var AutoLoad
	 */
	var $al;

	/**
	 * @param string $field
	 * @param string $value
	 */
	function __construct($field, $value = '') {
//		parent::__construct();
		$this->field = $field;
		$this->setValue($value);
		$this->al = AutoLoad::getInstance();
	}

	/**
	 * @param int $value
	 * @return mixed|void
	 */
	function setValue($value) {
		$this->value = $value;
	}

	function render() {
		$content = [];
		$content[] = $this->form->getInput('input', $this->field, $this->value, [
			'class' => ifsetor($this->desc['class']),
		], ifsetor($this->desc['class']));

		if ($this->value) {
//			$map = new StaticMapOSM($this->value);
//			$map = new StaticMapGM($this->value);
			$config = Config::getInstance();
			$map = $config->getStaticMapMQ($this->value);
			$content[] = $map->render();
		}
		return $content;
	}

}
