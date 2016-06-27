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
	function __construct($fieldName, LazyTreeOptions $tree) {
		$this->setField($fieldName);
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
			<div class="jqueryFileTreeExtra">
				<div>
					<img src="skin/default/img/down.gif" class="trigger">
					<input id="'.$tree->receptorID.'_title" value="'.$specificTree->getNameFor($tree->selectedNode).'">
				</div>
				<div id="'.$tree->containerID.'" class="jqueryFileTreeContainer"></div>
				<script>
					jQuery(document).ready(function () {
						jqueryFileTreeStart("'.$tree->containerID.'", "'.$tree->receptorID.'", "'.$strField.'");
					});
				</script>
			</div>
		');

		//unset($GLOBALS['HTMLHEADER']['prototype']);
		//unset($GLOBALS['HTMLHEADER']['scriptaculous']);
		Index::getInstance()->addJQuery();
		$GLOBALS['HTMLHEADER']['jQueryReady'] = '<script src="script/jQueryReady.js"></script>';
		$GLOBALS['HTMLHEADER']['jQueryFileTree'] = '
			<link type="text/css" href="lib/jqueryFileTree/jqueryFileTree.css" rel="stylesheet" />
			<script src="lib/jqueryFileTree/jqueryFileTree.js"></script>
		';
		$GLOBALS['HTMLHEADER']['jqueryFileTreeStart'] = '<script src="lib/jqueryFileTree/jqueryFileTreeStart.js"></script>';
		//debug($tree->openTreeNodes);

		$content = $this->form->stdout;
		$this->form->stdout = $tmp;
		return $content;
	}

}
