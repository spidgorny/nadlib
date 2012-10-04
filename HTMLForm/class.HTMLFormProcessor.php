<?php

abstract class HTMLFormProcessor extends Controller {
	protected $prefix = __CLASS__;
	protected $default = array();
	protected $desc = array();
	protected $validated = false;
	protected $ajax = true;
	protected $submitButton = '';

	function __construct(array $default = array()) {
		parent::__construct();
		$this->prefix = get_class($this);
		$this->default = $default ? $default : $this->default;
		$this->desc = $this->getDesc();
		if (Request::getInstance()->is_set($this->prefix)) {
			$urlParams = Request::getInstance()->getArray($this->prefix);
			$this->desc = HTMLFormTable::fillValues($this->desc, $urlParams);
			$v = new HTMLFormValidate($this->desc);
			$this->validated = $v->validate();
			$this->desc = $v->getDesc();
		} else {
			$this->desc = HTMLFormTable::fillValues($this->desc, $this->default);
		}
		//debug($this->desc);
		assert($this->submitButton != '');
		$this->submitButton = strip_tags(__($this->submitButton));
	}

	abstract function getDesc();

	/**
	 * If inherited can be used as both string and HTMLFormTable
	 * @return HTMLFormTable
	 */
	function render() {
		if ($this->validated) {
			$content = $this->onSuccess(HTMLFormTable::getValues($this->desc));
		} else {
			$content = $this->getForm();
		}
		return $content;
	}

	function getForm(HTMLFormTable $preForm = NULL) {
		$f = $preForm ?: new HTMLFormTable($this->desc);
		if ($this->ajax) {
			$f->formMore = 'onsubmit="return ajaxSubmitForm(this);"';
		}
		$f->method('POST');
		$f->hidden('c', $this->prefix);
		$f->hidden('ajax', $this->ajax);
		$f->prefix($this->prefix);
		$f->showForm();
		$f->prefix('');
		$f->submit($this->submitButton, '', array('class' => 'btn'));
		return $f;
	}

	function __toString() {
		return '<div class="HTMLFormProcessor">'.$this->render().'</div>';
	}

	abstract function onSuccess(array $data);

}
