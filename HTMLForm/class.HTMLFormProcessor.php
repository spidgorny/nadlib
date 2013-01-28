<?php

abstract class HTMLFormProcessor extends AppController {
	protected $prefix = __CLASS__;
	protected $default = array();
	protected $desc = array();

	/**
	 * @var HTMLFormValidate
	 */
	protected $validator;

	/**
	 * Stored result of the validation. HTMLFormValidate doesn't cache the result
	 * @var bool
	 */
	protected $validated = false;

	protected $ajax = true;

	protected $submitButton = '';

	/**
	 * @var HTMLFormTable
	 */
	protected $form;

	/**
	 * Distinguishes initial display of the form (false) from after submit (true)
	 * @var bool
	 */
	protected $submitted = false;

	function __construct(array $default = array()) {
		parent::__construct();
		$this->prefix = get_class($this);
		$this->default = $default ? $default : $this->default;
		assert($this->submitButton != '');
		$this->submitButton = strip_tags(__($this->submitButton));
		$this->submitted = $this->request->is_set($this->prefix);
	}

	/**
	 * The idea is to remove all slow operations outside of the constructor.
	 */
	function postInit() {
		$this->desc = $this->getDesc();
		$this->form = $this->getForm();
		//debug($this->desc);
		//debug($this->prefix);
		if ($this->submitted) {
			//$urlParams = $this->request->getArray($this->prefix);
			//$this->desc = HTMLFormTable::fillValues($this->desc, $urlParams);
			$subRequest = $this->request->getSubRequest($this->prefix);
			//debug('submit detected', $this->prefix, sizeof($subRequest->getAll()), implode(', ', array_keys($subRequest->getAll())));
			$this->form->importValues($subRequest);
			$this->desc = $this->form->desc;
			//debug($this->form->desc);
			$this->validator = new HTMLFormValidate($this->desc);
			$this->validated = $this->validator->validate();
			$this->desc = $this->validator->getDesc();
			$this->form->desc = $this->desc;
		} else {
			//$this->desc = HTMLFormTable::fillValues($this->desc, $this->default);
			$this->form->importValues($this->default);
			$this->desc = $this->form->desc;
		}
	}

	abstract function getDesc();

	/**
	 * If inherited can be used as both string and HTMLFormTable
	 * @return HTMLFormTable
	 */
	function render() {
		$content = '';
		//debug($this->validated);
		//$errors = AP($this->desc)->column('error')->filter()->getData();
		//debug($errors);
		//debug($this->desc);
		if ($this->validated) {
			$content .= $this->onSuccess($this->form->getValues());
		} else {
			$content .= '<div class="error alert alert-error">'.__('The form is not complete. Please check the comments next to each field below.').'</div>';
			$this->form->prefix($this->prefix);
			$this->form->showForm();
			$this->form->prefix('');
			$this->form->submit($this->submitButton, '', array('class' => 'btn'));
			$content .= $this->form;
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
		return $f;
	}

	function __toString() {
		return '<div class="HTMLFormProcessor">'.$this->render().'</div>';
	}

	abstract function onSuccess(array $data);

}
