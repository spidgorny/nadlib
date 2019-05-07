<?php

/**
 * Class Scaffold
 * @deprecated - use HTMLFormProcessor if you only need the edit form
 */
abstract class Scaffold extends AppController
{

	/**
	 * @var string
	 */
	protected $table = 'sometable in Scaffold';

	/** @var HTMLFormTable */
	protected $form;

	/**
	 * Name of the form fields: scaffold[asd]
	 */
	protected $formPrefix = 'scaffold';

	/**
	 * @var array
	 * @deprecated    - why? Use Collection instead?
	 */
	protected $thes = array();

	/**
	 * Button label
	 * @var string
	 */
	protected $addButton = 'Add';

	/**
	 * Button label
	 * @var string
	 */
	protected $updateButton = 'Save';

	/**
	 * Default function to display.
	 * showForm
	 * showEdit
	 * add
	 * edit
	 * @var string
	 */
	protected $action = '';

	/**
	 * OODBase based model class to modify database.
	 *
	 * @var OODBase
	 */
	public $model;

	/**
	 * extra attributes for the form like onSubmit
	 * @var string
	 */
	protected $formMore = '';

	/**
	 * edited element
	 * @var int
	 */
	protected $id;

	/**
	 * Either from the <FORM> or default from DB?
	 * @var array
	 */
	public $data = [];

	protected $editIcon = '<img src="../../img/stock-edit-16.png" alt="Edit"/>';

	/**
	 * @var Request
	 */
	protected $subRequest;

	function __construct()
	{
		parent::__construct();
		$this->translateThes();
		$this->addButton = __($this->addButton);
		$this->updateButton = __($this->updateButton);
		$this->action = $this->request->getCoalesce('action', $this->action);
		$this->id = $this->request->getInt($this->table . '_id');
		if (!$this->id) {
			$this->id = $this->request->getInt($this->table . '.id'); // NON AJAX POST
		}
		if (!$this->id) {
			// don't do it. It can be ID of anything (parent record)
			//$this->id = $this->request->getInt('id');
		}

		$this->subRequest = $this->request->getSubRequest($this->formPrefix);
		$this->setModel();    // uses $this->id

		$this->form = new HTMLFormTable();
		//debug($this->request->isSubmit(), $this->formPrefix, $this->request->getArray($this->formPrefix));
		if ($this->request->isSubmit()) {
			$this->data = $this->subRequest->getAll();
		} else {
			$this->data = $this->model->data;
		}
//		debug($this->data);
		$this->form->desc = $this->getDesc((array)$this->data);
		//debug($this->form->desc);
		nodebug(array(
			'id' => $this->id,
			'isSubmit' => $this->request->isSubmit(),
			'formPrefix' => $this->formPrefix,
			'data' => $this->data,
			'model' => $this->model));
	}

	/**
	 * Sets $this->model to something. Can use $this->id for editing mode
	 */
	abstract function setModel();

	public function render()
	{
		$content = [];
//		debug($this->action);
		switch ($this->action) {
			case 'showForm':
				$content[] = $this->showForm();
				break;
			case 'showEdit':
				$content[] = $this->showEditForm();
				break;
			case 'add':
			case 'update':
				$content[] = $this->showPerform();
				break;
			default:    // view table
				if (method_exists($this, $this->action . 'Action')) {
					$content[] = call_user_func(array($this, $this->action . 'Action'));
				} else {
					$content[] = $this->showDefault();
				}
				break;
		}
		return $content;
	}

	function showDefault()
	{
		$content[] = $this->showTable();
		$content[] = $this->showButtons();
		$content[] = '<div id="' . $this->formPrefix . '"></div>'; // container for all AJAX add/edit forms
		return $content;
	}

	/**
	 * Collection is way better to display raw data
	 *
	 * @return string
	 * @deprecated
	 */
	public function showTable()
	{
		$data = array($this->model->data);
		$data = $this->processData($data);

		if ($data) {
			debug($this->model->data, $data);
			$s = new slTable($data, 'class="nospacing spaceBelow"');
			$s->thes($this->thes);
			$content = $s->getContent();
		} else {
			$content = '<div class="message">No data found.</div>';
		}
		return $content;
	}

	function processData(array $data)
	{
		foreach ($data as &$row) {
			$row['edit'] = $this->getEditIcon($row['id']);
		}
		return $data;
	}

	public function getEditIcon($id)
	{
		//makeAjaxLink
		$aTag = $this->makeLink($this->editIcon, array(
			'c' => get_class($this),
			'pageType' => get_class($this),
			'ajax' => TRUE,
			'action' => 'showEdit',
			$this->table . '.id' => $id,
		), $this->formPrefix);
		$href = $aTag->attr['href'];
		/** @var URL $href */
		$aTag->attr['href'] = $href->buildQuery();
		return $aTag;
	}

	protected function showButtons()
	{
		$content = $this->makeAjaxLink('<button>' . $this->addButton . '</button>', array(
			'c' => get_class($this),
			'ajax' => TRUE,
			'action' => 'showForm',
		), $this->formPrefix, '', array('class' => "button"));
		return $content;
	}

	public function showForm()
	{
		if ($this->action == 'showEdit' || $this->action == 'update') {
			$f = $this->showEditForm();
		} else {
			$f = $this->getForm();
			$f->prefix('');
			$f->submit($this->addButton, array(
				'class' => 'btn btn-primary',
			));
		}
		return $f;
	}

	/**
	 * Will be called by showForm() if the action is showEdit
	 * @return HTMLFormTable
	 */
	protected function showEditForm()
	{
		$override = array(
			$this->table . '.id' => $this->id,
		);

		/*		if ($this->desc['submit']) {
					$this->desc['submit']['value'] = $this->updateButton;
				}
		*/
		$f = $this->getForm('update');
		$f->prefix('');
		foreach ($override as $key => $val) {
			$f->hidden($key, $val);
		}
		$f->button('<span class="glyphicon glyphicon-floppy-disk"></span> ' . $this->updateButton, array(
			'type' => 'submit',
			'class' => 'btn btn-primary',
		));
		return $f;
	}

	/**
	 * Return nothing or false to indicate success.
	 * $this->insertRecord should return nothing?!?
	 *
	 * @return string
	 * @throws Exception
	 */
	public function showPerform()
	{
		$content = [];
		//$userData = $this->request->getArray($this->formPrefix);
		//debug($userData, $formPrefix);

		//$desc = $this->getDesc($userData);
		//$f = new HTMLFormTable();
		//$desc = $f->fillValues($desc, $userData); // commented not to overwrite
		$v = new HTMLFormValidate($this->form);
		if ($v->validate()) {
			try {
				switch ($this->action) {
					case 'add':
						$content[] = $this->insertRecord($this->data);
						break;
					case 'update':
						$content[] = $this->updateRecord($this->data);
						break;
					default:
						{
							debug(__METHOD__);
							throw new Exception(__METHOD__);
						}
				}
			} catch (DatabaseException $e) {
				$content[] = '<p class="error ui-state-error">We were unable to perform the operation because "' . $e->getMessage() . '". Please check your form fields and retry. Please let us know if it still doesn\'t work using the <a href="?c=Contact">contact form</a>.';
				debug($e->getQuery());
				$content[] = $this->showForm();
			} catch (PDOException $e) {
				debug($e->getMessage());
				debug($this->db->lastQuery);
			}
		} else {
			//$desc = $v->getDesc();
			$content[] = '<div class="error ui-state-error">Validation failed. Check your form below:</div>';
			$content[] = $v->getErrorList();
			$content[] = $this->showForm();
		}
		return $content;
	}

	function insertRecord(array $userData)
	{
		$this->model->insert($userData);
		return $this->afterInsert($userData);
	}

	function updateRecord(array $userData)
	{
		//debug($this->model->data, $userData);
		$this->model->update($userData);    // update() returns nothing
		//debug($this->model->data, $this->model->lastQuery);
		return $this->afterUpdate($userData);
	}

	/**
	 * Needs to implement data into the desc internally!!!
	 * Please use HTMLFormTable::fillValues()
	 * @param array $data - the source data of the edited record, if in edit more
	 * @return array
	 */
	protected function getDesc(array $data = NULL)
	{
		$desc = array(
			'name' => array(
				'label' => 'Name',
			),
		);
		$this->form->desc = $desc;
		$this->form->fill($data);
		return $this->form->desc;
	}

	/**
	 * Default is add action, override to update
	 *
	 * @param string $action
	 * @return HTMLFormTable
	 */
	protected function getForm($action = 'add')
	{
		$this->form->method('POST');
		$this->form->hidden('c', get_class($this));
		$this->form->hidden('pageType', get_class($this));
		$this->form->hidden('action', $action);
		//$this->form->hidden('ajax', TRUE);        // add this to getDesc()
		$this->form->prefix($this->formPrefix);
		$this->form->showForm();
		//$this->form->submit($this->addButton);    // because it's used for edit
		$this->form->formMore = $this->formMore;
		return $this->form;
	}

	/**
	 * @deprecated
	 */
	function translateThes()
	{
		// translate thes
		foreach ($this->thes as $key => &$trans) {
			if (is_string($trans) && $trans) {
				$trans = __($trans);
			} else if (is_array($trans) && ifsetor($trans['name'])) {
				$trans['name'] = __($trans['name']);
			}
		}
	}

	function getDescFromThes()
	{
		$special = array('id', 'match', 'mtime', 'muser');
		$desc = array();
		foreach ($this->model->thes as $key => $k) {
			$k = is_array($k) ? $k : array('name' => $k);
			if (!in_array($key, $special) && $k['showSingle'] !== false) {
				$desc[$key] = array(
						'label' => $k['name'],
						'type' => $k['type'],
						'value' => $this->model->data[$key],
					) + $k;
				if ($k['type'] == 'combo') {
					$desc[$key]['options'] = $this->db->getTableOptions(
						$this->model->table,
						$key, array(),
						'ORDER BY ' . $this->db->quoteKey($key), $key);
				}
			}
		}
		return $desc;
	}

	function afterInsert(array $userData)
	{
		return '<div class="success">' . __('Inserted') . '</div>';
	}

	function afterUpdate(array $userData)
	{
		return '<div class="success">' . __('Updated') . '</div>';
	}

}
