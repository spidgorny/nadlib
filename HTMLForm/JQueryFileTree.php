<?php

class JQueryFileTree extends HTMLFormType {

	/**
	 * @var LazyTreeOptions
	 */
	var $tree;

	/**
	 * @param string/array $fieldName
	 * @param LazyTreeOptions $tree
	 */
	function __construct(LazyTreeOptions $tree) {
		$this->setTree($tree);
		$this->setForm(new HTMLForm());
	}

	function setTree(LazyTreeOptions $tree) {
		$this->tree = $tree;
	}

	function render() {
		$tmp = $this->form->stdout;
		$this->form->stdout = '';

		$fieldName = $this->field;
		$tree = $this->tree;
		$textName = $this->form->getName($fieldName, '', TRUE);

		$fullPath = array_merge(
			$this->form->getPrefix(),
			(is_array($fieldName) ? $fieldName : array($fieldName))
		);
		$strField = implode('_', $fullPath);

		$tree->sessionID = $strField;
		$tree->receptorID = 'treeNodeClickValue_'.$strField;
		$tree->containerID = 'treeNodeClickTree_'.$strField;
		$_SESSION['jqueryFileTree'][$strField] = clone $tree; // will be garbage collected

		$this->form->hidden($fieldName, $tree->selectedNode, 'id="'.$tree->receptorID.'"');
		if (is_array($tree->rootID)) {
			$start = NULL;
		} else {
			$start = $tree->rootID;
		}
		$specificTree = new $tree->class($start, $tree);
		$specificTree->id = $tree->rootID;  // array

		/* @var $specificTree LazyTreeBase */
		$this->form->text('
			<div class="jqueryFileTreeExtra" 
			containerID="'.$tree->containerID.'"
			receptorID="'.$tree->receptorID.'"
			strField="'.$strField.'"
			>
				<div>
					<img src="skin/default/img/down.gif" class="trigger" />
					<input id="'.$tree->receptorID.'_title" 
					value="'.$specificTree->getNameFor($tree->selectedNode).'" 
					placeholder="Search starts after three letters"
					title="Search starts after three letters"
					/>
				</div>
				<div id="'.$tree->containerID.'" 
				class="jqueryFileTreeContainer"></div>
			</div>
		');

		//unset($GLOBALS['HTMLHEADER']['prototype']);
		//unset($GLOBALS['HTMLHEADER']['scriptaculous']);
		Index::getInstance()
			->addJQuery()
			->addJS('script/jQueryReady.js')
			->addCSS("lib/jqueryFileTree/jqueryFileTree.css")
			->addJS("lib/jqueryFileTree/jqueryFileTree.js")
			->addJS('lib/jqueryFileTree/jqueryFileTreeStart.js');
		//debug($tree->openTreeNodes);

		$content = $this->form->stdout;
		$this->form->stdout = $tmp;
		return $content;
	}

}
