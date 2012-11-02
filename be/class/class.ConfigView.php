<?php

class ConfigView extends AppController {

	protected $prefix = __CLASS__;

	protected $typeMap = array(
		'string' => 'input',
		'boolean' => 'checkbox',
		'integer' => 'input',
	);

	function __construct() {
		parent::__construct();
		$this->file = dirname(__FILE__).'/../../../class/config.yaml';
	}

	function render() {
		$this->performAction();
		$data = Spyc::YAMLLoad($this->file);
		//$content = getDebug($data);

		$f = new HTMLFormTable();
		$f->prefix($this->prefix);
		foreach ($data as $class => $props) {
			$f->fieldset($class);
			$desc = array();
			foreach ($props as $key => $val) {
				$desc[$class.'['.$key.']'] = array(
					'label' => $key,
					'type' => $this->typeMap[gettype($val)],
					'value' => $val,
					'set0' => true,
					'optional' => true,
				);
			}
			$f->showForm($desc);
		}

		$f->prefix('');
		$f->hidden('action', 'save');
		$f->submit('Save');
		$content .= $f;

		$content .= '<style>.tdlabel { width: 10em; } </style>';
		return $content;
	}

	function saveAction() {
		$data = $this->request->getArray($this->prefix);
		foreach ($data as $class => &$props) {
			foreach ($props as $key => &$val) {
				if ($val === "0") {
					$val = false;
				}
				if ($val === "1") {
					$val = true;
				}
			}
		}
		//debug($data);
		$yaml = Spyc::YAMLDump($data);
		//debug($yaml);
		file_put_contents($this->file, $yaml);
	}

}
