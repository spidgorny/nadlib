<?php

class AjaxTreeOld extends HTMLFormType {

	function __construct($field, $value, array $desc) {
		$this->setField($field);
		$this->setValue($value);
		$this->desc = $desc;
		$this->setForm(new HTMLForm());
	}

	/**
	 * $desc['selectID'] - <select name="projects" id="projects">
	 * $desc['treeDivID'] - <div id="treeDivID" style="display: none"></div>
	 * $desc['tableName'] - SELECT * FROM tableName ...
	 * $desc['tableRoot'] - ... WHERE pid = tableRoot
	 * $desc['tableTitle'] - SELECT id, tableTitle FROM ...
	 * $desc['paddedID'] - paddedID.innerHTML = tree.toString()
	 */
	function getOpenButton() {
		$desc = $this->desc;
		$GLOBALS['HTMLHEADER']['ajaxTreeOpen'] = '<script src="js/ajaxTreeOpen.js"></script>';
		$GLOBALS['HTMLHEADER']['globalMouse'] = '<script src="js/globalMouse.js"></script>';
		$GLOBALS['HTMLHEADER']['dragWindows'] = '<script src="js/dragWindows.js"></script>';
		$this->stdout .= new HTMLTag('a', array(
			'href' => '#',
			'onclick' => 'ajaxTreeOpen(
				\''.$desc['selectID'].'\',
				\''.$desc['treeDivID'].'\',
				\''.$desc['tableName'].'\',
				\''.json_encode($desc['tableRoot']).'\',
				\''.$desc['tableTitle'].'\',
				\''.(isset($desc['paddedID'])?$desc['paddedID']:'').'\',
				\''.$desc['categoryID'].'\',
				\''.$desc['onlyLeaves'].'\',
				\''.$desc['selected'].'\'
			);
			'.$desc['onclickMore'].'
			return false;
		'), '<img
			src="img/tb_folder.gif"
			title="'.$desc['ButtonTitle'].'">', true);
		$style = 'display: none;
		position: absolute;
		left: 0;
		top: 0;
		width: 480px;
		height: auto;
		border: solid 3px #8FBC8F;
		margin: 3px;
		background-color: white;
		az-index: 98;';
		//$this->stdout .= '<div id="'.$desc['treeDivID'].'" style="'.$style.'"></div>';
		/** @var Extension $controller */
		$controller = Index::getInstance()->controller;
		$this->stdout .= $controller->encloseOld('Tree-Element Selector', '',
			array(
				'outerStyle' => $style,
				'foldable' => FALSE,
				'outerID' => $desc['treeDivID'],
				'paddedID' => (isset($desc['paddedID'])?$desc['paddedID']:''),
				'closable' => TRUE,
				'absolute' => TRUE,
				'paddedStyle' => 'height: 640px; overflow: auto;',
				'titleMore' => 'onmousedown="dragStart(event, \''.$desc['treeDivID'].'\')" style="cursor: move;"',
			));
		return $this->stdout;
	}

	function render() {
		$tmp = $this->form->stdout;
		$this->form->stdout = '';

		$fieldName = $this->field;
		$fieldValue = $this->value;
		$desc = $this->desc;
		$desc['more'] = isset($desc['more']) ? $desc['more'] : NULL;
		$desc['size'] = isset($desc['size']) ? $desc['size'] : NULL;
		$desc['cursor'] = isset($desc['cursor']) ? $desc['cursor'] : NULL;
		$desc['readonly'] = isset($desc['readonly']) ? $desc['readonly'] : NULL;
		$this->form->text('<nobr>');
		$this->form->hidden($fieldName, $fieldValue,
			'id="'.ifsetor($desc['selectID']).'"');
		if (is_array($fieldName)) {
			$fieldName[sizeof($fieldName) - 1] = end($fieldName) . '_name';
		} else {
			$fieldName .= '_name';
		}
		$this->form->input($fieldName, $desc['valueName'],
			'style="width: '.$desc['size'].'"
			readonly
			id="'.$desc['selectID'].'_name" '.
			$desc['more']);
		$this->form->text('</td><td>');
		$this->form->text($this->getOpenButton());
		$this->form->text('</nobr>');

		$content = $this->form->stdout;
		$this->form->stdout = $tmp;
		return $content;
	}

}
