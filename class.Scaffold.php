<?php

abstract class Scaffold extends Controller {
	protected $table = 'override this for sure';

	/**
	 * Name of the form fields: scaffold[asd]
	 */
	protected $formPrefix = 'scaffold';
	protected $thes = array();
	protected $addButton = 'Add';
	protected $updateButton = 'Save';
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
		$this->updateButton = __($this->updateButton);
		$this->action = $this->request->getTrim('action');
		$this->id = $this->request->getInt($this->table.'_id');
		if (!$this->id) {
			$this->id = $this->request->getInt($this->table.'.id'); // NON AJAX POST
		}
		if (!$this->id) {
			$this->id = $this->request->getInt('id');
		}
		$this->setModel();	// uses $this->id

		//debug(array('isSubmit' => $this->request->isSubmit()));
		if ($this->request->isSubmit()/* || $this->action == 'checkAvailability'*/) {
			$this->data = $this->request->getArray($this->formPrefix);
		} else {
			//$this->data = end($this->fetchData(array('id' => $this->id)));
			$this->data = $this->model->data;
			//debug($this->data);
		}
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
				$content .= $this->showForm(array(
					'action' => 'update',
					$this->table.'.id' => $this->id,
				));
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
		$data = $this->fetchData();
		$data = $this->processData($data);

		if ($data) {
			$s = new slTable($data);
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

	public function showForm(array $override = array()) {
		$f = $this->getForm($action = $override['action'] ? $override['action'] : 'add');
		$f->prefix('');
		unset($override['action']);
		foreach ($override as $key => $val) {
			$f->hidden($key, $val);
		}
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
			$content .= '<div class="message ui-state-error">Validation failed. Check your form below:</div>';
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
	 * @param array $data - the source data of the edited record, if in edit more
	 * @return array
	 */
	protected function getDesc(array $data = NULL) {
		return array();
	}

	/**
	 * Default is add action, override to update
	 *
	 * @param array $desc
	 * @param unknown_type $action
	 * @return unknown
	 */
	protected function getForm($action = 'add') {
		$f = new HTMLFormTable();
		$f->hidden('c', get_class($this));
		$f->hidden('action', $action);
		$f->hidden('ajax', TRUE);
		$f->prefix($this->formPrefix);
		$f->showForm($this->desc);
		$f->submit($this->addButton);
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
		return $res;
	}

	function updateRecord(array $userData) {
		$res = $this->model->update($userData);	// update() returns nothing
		return $res;
	}

}
