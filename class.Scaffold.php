<?php

abstract class Scaffold extends Controller {
	protected $table = 'override this for sure';
	/**
	 * Name of the form fields: scaffold[asd]
	 *
	 */
	protected $formPrefix = 'scaffold';
	protected $thes = array();
	protected $addButton = 'Add';
	protected $action;
	/**
	 * OODBase based model class to modify database.
	 *
	 * @var OODBase
	 */
	protected $model;
	protected $id; 				// edited element
	protected $data;
	protected $desc;

	protected $editIcon = '<img src="img/stock-edit-16.png"/>';

	function __construct() {
		parent::__construct();
		$this->translateThes();
		$this->addButton = __($this->addButton);
		$this->action = $this->request->getTrim('action');
		$this->id = $this->request->getInt($this->table.'_id');
		if (!$this->id) {
			$this->id = $this->request->getInt($this->table.'.id'); // NON AJAX POST
		}
		if (!$this->id) {
			$this->id = $this->request->getInt('id');
		}
		$this->setModel();	// uses $this->id

		if ($this->request->isSubmit()) {
			$this->data = $this->request->getArray($this->formPrefix);
		} else {
			$this->data = $this->model->data;
		}
		//debug($this->id, $this->request->isSubmit(), $this->data, $this->model);
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
				$content .= $this->showForm();
			break;
			case 'showEdit':
				$content .= $this->showEditForm();
			break;
			case 'add':
				$content .= $this->showPerform($this->action);
			break;
			case 'update':
				$content .= $this->showPerform($this->action, $this->id);
			break;
			default:
				$content .= $this->showTable();
				$content .= $this->showButtons();
				$content .= '<div id="'.$this->formPrefix.'"></div>'; // container for all AJAX add/edit forms
			break;
		}
		return $content;
	}

	public function showTable() {
        $content = '';
		$data = $this->fetchData();

		foreach ($data as &$row) {
			$row['edit'] = $this->getEditIcon($row['id']);
		}

		if ($data) {
			$s = new slTable($data);
			$s->thes($this->thes);
			$content .= $s->getContent();
		} else {
			$content .= '<div class="message">No data found.</div>';
		}
		return $content;
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
		$f = $this->getForm();
		$override = array(
			'action' => 'update',
			$this->table.'.id' => $this->id,
		);
		$f->prefix('');
		foreach ($override as $key => $val) {
			$f->hidden($key, $val);
		}
		//debug($override);
		return $f;
	}

	/**
	 * Return nothing or false to indicate success.
	 * $this->insertRecord should return nothing?!?
	 *
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @return unknown
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
					case 'add': $content .= $this->insertRecord($this->data); break;
					case 'update': $content .= $this->updateRecord($this->data); break;
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
			$content .= '<div class="ui-state-error">Validation failed. Check your form below:</div>';
			$content .= $this->showForm();
			//debug($desc['participants'], $userData['participants']);
		}
		return $content;
	}

	protected function fetchData(array $where = array()) {
		$data = $this->db->fetchSelectQuery($this->table, $where, 'ORDER BY id');
		return $data;
	}

	/**
	 * Needs to implement data into the desc internally
	 * @param array $data
	 * @return type
	 */
	protected function getDesc(array $data = NULL) {
		return array();
	}

	protected function getForm() {
		$f = new HTMLFormTable('.');
		$f->hidden('c', get_class($this));
		$f->hidden('pageType', get_class($this));
		$f->hidden('action', 'add');
		$f->hidden('ajax', TRUE);
		$f->prefix($this->formPrefix);
		$f->showForm($this->desc);
		$f->submit();
		return $f;
	}

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

	function insertRecord(array $userData) {
		$res = $this->model->insert($userData);
		return $this->afterInsert();
	}

	function updateRecord(array $userData) {
		$res = $this->model->update($userData);	// update() returns nothing
		return $this->afterUpdate();
	}

	function afterInsert() {
		return 'Inserted';
	}

	function afterUpdate() {
		return 'Updated';
	}

}
