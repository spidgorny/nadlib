<?php

/**
 * Class HTMLFormProcessor - allows quick implementation of the HTML form with validation
 * You only need to implement
 * - getDesc();
 * - onSuccess();
 * - submitButton
 */
abstract class HTMLFormProcessor extends SimpleController
{

	/**
	 * @var array|Request
	 */
	public $default = [];

	public $ajax = true;

	/**
	 * For debugging
	 * @var array
	 */
	public $method = [];
	public $postUrl;
	protected string $prefix;
	/**
	 * @var HTMLFormValidate
	 */
	protected $validator;
	/**
	 * Stored result of the validation. HTMLFormValidate doesn't cache the result
	 * @var bool
	 */
	protected $validated = false;
	protected string $submitButton = 'Save';
	/**
	 * @var HTMLFormTable
	 */
	protected $form;
	/**
	 * Distinguishes initial display of the form (false) from after submit (true)
	 * @var bool
	 */
	protected $submitted = false;

	public function __construct(array $default = [])
	{
		parent::__construct();
		$this->prefix = get_class($this);
		$this->default = $default ?: $this->default;
		assert($this->submitButton !== '');
		$this->submitButton = strip_tags(__($this->submitButton));
		$this->submitted = $this->request->is_set($this->prefix);
		//debug($this->prefix, $this->request->is_set($this->prefix));
	}

	public function __toString(): string
	{
		return '<div class="HTMLFormProcessor">' . $this->render() . '</div>';
	}

	/**
	 * If inherited can be used as both string and HTMLFormTable
	 * @return string[]|ToStringable
	 * @throws Exception
	 */
	public function render()
	{
		$content = '';
		if (!$this->form) {
			$this->postInit();
		}

		//debug($this->validated);
		//$errors = AP($this->desc)->column('error')->filter()->getData();
		//debug($errors);
		//debug($this->desc);
		if ($this->validated) {
			//$data = $this->form->getValues();	// doesn't work with multidimensional
			$data = $this->request->getArray($this->prefix);
			$content .= $this->s($this->onSuccess($data));
		} else {
			if ($this->submitted) {
				$content .= '<div class="error alert alert-error ui-state-error padding">' .
					__('The form is not complete. Please check the comments next to each field below.') . '</div>';
			}

			$content .= $this->s($this->showForm());
		}

		return $this->encloseInAA($content, $this->title);
	}

	/**
	 * The idea is to remove all slow operations outside of the constructor.
	 * Who's gonna call this function? Index?
	 */
	public function postInit(): void
	{
		$this->form = new HTMLFormTable();    // needed sometime in getDesc
		$this->form->setDesc($this->getDesc());
		$this->form = $this->getForm($this->form);        // $this->desc will be used inside
		//debug($this->desc);
		//debug($this->prefix);
		if ($this->submitted) {
			$this->method[] = '$this->submitted = true';
			//$urlParams = $this->request->getArray($this->prefix);
			//$this->desc = HTMLFormTable::fillValues($this->desc, $urlParams);
			$subRequest = $this->request->getSubRequest($this->prefix);
			//debug('submit detected', $this->prefix, sizeof($subRequest->getAll()), implode(', ', array_keys($subRequest->getAll())));
			$this->form->importValues($subRequest);
			//debug('importValues', $subRequest, $this->form->getValues(), $this->form->desc['begins']);
			$this->method[] = '$this->desc = $this->form->importValues($subRequest($this->prefix))';

			$this->validator = new HTMLFormValidate($this->form);
			$this->validated = $this->validator->validate();
			$this->form->desc = $this->validator->getDesc();
			$this->method[] = '$this->desc = $this->validator->getDesc()';
		} else {
			$this->method[] = '$this->submitted = false';
			//$this->desc = HTMLFormTable::fillValues($this->desc, $this->default);
			//debug($this->default);
			if ($this->default) {
				$this->form->importValues($this->default instanceof Request
					? $this->default
					: new Request($this->default));
				$this->method[] = '$this->desc = $this->form->importValues($this->default)';
			} else {
				$this->method[] = '! import $this->default';
			}
		}
	}

	abstract public function getDesc();

	public function getForm(HTMLFormTable $preForm = null)
	{
		$f = $preForm ?: $this->form;
		if ($this->ajax) {
			$f->formMore['onsubmit'] = "return ajaxSubmitForm(this);";
		}

		$f->method('POST');
		$f->action($this->postUrl);
		$f->hidden('ajax', $this->ajax);
		return $f;
	}

	abstract public function onSuccess(array $data);

	public function showForm()
	{
		if (!$this->form) {
			throw new \RuntimeException(__METHOD__ . ': initialize form with getForm()');
		}

		$this->form->prefix($this->prefix);
		$this->form->showForm();
		$this->form->prefix('');
		$this->form->submit($this->submitButton, ['class' => 'btn btn-success']);
		return $this->form->getContent();
	}

}
