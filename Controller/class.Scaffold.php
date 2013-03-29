<?php

abstract class Scaffold extends AppController {
	protected $table = 'sometable in Scaffold';

	/** @var HTMLFormTable */
	protected $form;

	/**
	 * Name of the form fields: scaffold[asd]
	 */
	protected $formPrefix = 'scaffold';

	/**
	 * @var array
	 * @deprecated	- why? Use Collection instead?
	 */
	protected $thes = array();
	protected $addButton = 'Add';
	protected $updateButton = 'Save';
	protected $action = 'showEdit';

	/**
	 * OODBase based model class to modify database.
	 *
	 * @var OODBase
	 */
	protected $model;

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

	protected $data;

	protected $desc;

	protected $editIcon = '<img src="img/stock-edit-16.png"/>';

	function __construct() {
		parent::__construct();
		$this->translateThes();
		$this->addButton = __($this->addButton);
		$this->updateButton = __($this->updateButton);
		$this->action = $this->request->getCoalesce('action', $this->action);
		$this->id = $this->request->getInt($this->table.'_id');
		if (!$this->id) {
			$this->id = $this->request->getInt($this->table.'.id'); // NON AJAX POST
		}
		if (!$this->id) {
			$this->id = $this->request->getInt('id');
		}
		$this->setModel();	// uses $this->id

		$this->form = new HTMLFormTable();
		if ($this->request->isSubmit()) {
			$this->data = $this->request->getArray($this->formPrefix);
		} else {
			$this->data = $this->model->data;
		}
		nodebug(array(
			'id' => $this->id,
			'isSubmit' => $this->request->isSubmit(),
			'formPrefix' => $this->formPrefix,
			'data' => $this->data,
			'model' => $this->model));
		$this->desc = $this->getDesc($this->data);
	}

	/**
	 * Sets $this->model to something. Can use $this->id for editing mode
	 */
	abstract function setModel();

	public function render() {
		$content = '';
		switch ($this->action) {
			case 'showForm':
				$content = $this->showForm();
			break;
			case 'showEdit':
				$content .= $this->showEditForm();
			break;
			case 'add':
				$content = $this->showPerform($this->action);
			break;
			case 'update':
				$content = $this->showPerform($this->action, $this->id);
			break;
			default:
				$content = $this->showTable();
				$content .= $this->showButtons();
				$content .= '<div id="'.$this->formPrefix.'"></div>'; // container for all AJAX add/edit forms
			break;
		}
		return $content;
	}

	/**
	 * Collection is way better to display raw data
	 *
	 * @return string
	 * @deprecated
	 */
	public function showTable() {
		$data = array($this->model->data);
		$data = $this->processData($data);

		if ($data) {
			$s = new slTable($data, 'class="nospacing spaceBelow"');
			$s->thes($this->thes);
			$content = $s->getContent();
		} else {
			$content = '<div class="message">No data found.</div>';
		}
		return $content;
	}

	function processData(array $data) {
		foreach ($data as &$row) {
			$row['edit'] = $this->getEditIcon($row['id']);
		}
		return $data;
	}

	public function getEditIcon($id) {
		//makeAjaxLink
		$content = $this->makeLink($this->editIcon, array(
			'c' => get_class($this),
			'pageType' => get_class($this),
			'ajax' => TRUE,
			'action' => 'showEdit',
			$this->table.'.id' => $id,
		), $this->formPrefix);
		return $content;
	}

	protected function showButtons() {
		$content = $this->makeAjaxLink('<button>'.$this->addButton.'</button>', array(
			'c' => get_class($this),
			'ajax' => TRUE,
			'action' => 'showForm',
		), $this->formPrefix, '', 'class="button"');
		return $content;
	}

	public function showForm() {
		if ($this->action == 'showEdit' || $this->action == 'update') {
			$f = $this->showEditForm();
		} else {
			$f = $this->getForm();
		}
		return $f;
	}

	/**
	 * Will be called by showForm() if the action is showEdit
	 * @return HTMLFormTable
	 */
	protected function showEditForm() {
		$override = array(
			$this->table.'.id' => $this->id,
		);

		if ($this->desc['submit']) {
			$this->desc['submit']['value'] = $this->updateButton;
		}
		$f = $this->getForm('update');
		$f->prefix('');
		foreach ($override as $key => $val) {
			$f->hidden($key, $val);
		}
		return $f;
	}

	/**
	 * Return nothing or false to indicate success.
	 * $this->insertRecord should return nothing?!?
	 *
	 * @param string $action
	 * @param integer $id
	 * @throws Exception
	 * @return string
	 */
	public function showPerform($action, $id = NULL) {
		$content = '';
		//$userData = $this->request->getArray($this->formPrefix);
		//debug($userData, $formPrefix);

		//$desc = $this->getDesc($userData);
		//$desc = HTMLFormTable::fillValues($desc, $userData); // commented not to overwrite
		$v = new HTMLFormValidate($this->desc);
		if ($v->validate()) {
			try {
				switch ($action) {
					case 'add': $content = $this->insertRecord($this->data); break;
					case 'update': $content = $this->updateRecord($this->data); break;
					default: {
						debug(__METHOD__);
						throw new Exception(__METHOD__);
					}
				}
			} catch (Exception $e) {
				$content .= '<p class="ui-state-error">We were unable to perform the operation because "'.$e->getMessage().'". Please check your form fields and retry. Please let us know if it still doesn\'t work using the <a href="?c=Contact">contact form</a>.';
				$content .= $this->showForm();
			}
		} else {
			//$desc = $v->getDesc();
			$content .= '<div class="message ui-state-error">Validation failed. Check your form below:</div>';
			$content .= $this->showForm();
			//debug($desc['participants'], $userData['participants']);
		}
		return $content;
	}

	function insertRecord(array $userData) {
		$this->model->insert($userData);
		return $this->afterInsert($userData);
	}

	function updateRecord(array $userData) {
		$this->model->update($userData);	// update() returns nothing
		return $this->afterUpdate($userData);
	}

	/**
	 * Needs to implement data into the desc internally!!!
	 * Please use HTMLFormTable::fillValues()
	 * @param array $data - the source data of the edited record, if in edit more
	 * @return array
	 */
	protected function getDesc(array $data = NULL) {
		$desc = array(
			'submit' => array(
				'label' => '',
				'type' => 'submit',
				'value' => $this->addButton,
			),
		);
		return $desc;
	}

	/**
	 * Default is add action, override to update
	 *
	 * @param string $action
	 * @internal param array $desc
	 * @return HTMLFormTable
	 */
	protected function getForm($action = 'add') {
		$this->form->method('POST');
		$this->form->hidden('c', get_class($this));
		$this->form->hidden('pageType', get_class($this));
		$this->form->hidden('action', $action);
		$this->form->hidden('ajax', TRUE);
		$this->form->prefix($this->formPrefix);
		//debug($this->desc);
		$this->form->showForm($this->desc);
		//$this->form->submit($this->addButton);
		$this->form->formMore = $this->formMore;
		return $this->form;
	}

	/**
	 * @deprecated
	 */
	function translateThes() {
		// translate thes
		foreach ($this->thes as $key => &$trans) {
			if (is_string($trans) && $trans) {
				$trans = __($trans);
			} else if (is_array($trans) && $trans['name']) {
				$trans['name'] = __($trans['name']);
			}
		}
	}

	function getDescFromThes() {
		$desc = array();
		foreach ($this->model->thes as $key => $k) {
			$k = is_array($k) ? $k : array('name' => $k);
			if (!in_array($key, array('id', 'match', 'mtime', 'muser')) && $k['showSingle'] !== false) {
				$desc[$key] = array(
					'label' => $k['name'],
					'type' => $k['type'],
					'value' => $this->model->data[$key],
				) + $k;
				if ($k['type'] == 'combo') {
					$desc[$key]['options'] = Config::getInstance()->db->getTableOptions(
						$this->model->table,
						$key, array(),
						'ORDER BY '.$this->db->quoteKey($key), $key);
				}
			}
		}
		return $desc;
	}

	function afterInsert(array $userData) {
		return 'Inserted';
	}

	function afterUpdate(array $userData) {
		return 'Updated';
	}

}
