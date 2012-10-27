<?php

class ConfigView extends AppController {

	protected $prefix = __CLASS__;

	protected $typeMap = array(
		'string' => 'input',
		'boolean' => 'checkbox',
		'integer' => 'input',
	);

	function render() {
		$this->performAction();
		$file = dirname(__FILE__).'/../../../class/config.yaml';
		$data = Spyc::YAMLLoad($file);
		$content = getDebug($data);

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
		debug($data);
		$yaml = Spyc::YAMLDump($data);
		debug($yaml);
	}

}
