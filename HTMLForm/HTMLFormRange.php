<?php

class HTMLFormRange extends HTMLFormType {

	public $min = 0;

	public $max = 1440;		// 24*60

	public $value;

	public $step = 1;

	/**
	 * @var AutoLoad
	 */
	var $al;

	var $jsFile;

	/**
	 * @param string $field
	 * @param integer $value
	 */
	function __construct($field, $value) {
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
		$index = Index::getInstance();
		$index->addJQuery();
		$index->addJS($this->jsFile
			?: $this->al->nadlibFromDocRoot.'HTMLForm/HTMLFormRange.js');

		$content = new View($this->al->nadlibRoot.'HTMLForm/HTMLFormRange.phtml', $this);
		$fieldString = $this->form->getName($this->field, '', true);
		$fieldString = str_replace('[', '\\[', $fieldString);
		$fieldString = str_replace(']', '\\]', $fieldString);
		$content->fieldEscaped = $fieldString;
		return $content;
	}

}
