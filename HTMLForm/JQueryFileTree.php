<?php

class JQueryFileTree extends HTMLFormType
{

	/**
	 * @var LazyTreeOptions
	 */
	var $tree;

	/**
	 * @param string/array $fieldName
	 * @param LazyTreeOptions $tree
	 */
	function __construct(LazyTreeOptions $tree)
	{
		$this->setTree($tree);
		$this->setForm(new HTMLForm());
	}

	function setTree(LazyTreeOptions $tree)
	{
		$this->tree = $tree;
	}

	function render()
	{
		$tmp = $this->form->stdout;
		$this->form->stdout = '';

		$fieldName = $this->field;

		$fullPath = array_merge(
			$this->form->getPrefix(),
			(is_array($fieldName) ? $fieldName : array($fieldName))
		);
		$strField = implode('_', $fullPath);

		$this->tree->sessionID = $strField;
		$this->tree->receptorID = 'treeNodeClickValue_' . $strField;
		$this->tree->containerID = 'treeNodeClickTree_' . $strField;

//		debug($this->tree, $this->tree->getTreeInstance());

		$_SESSION['jqueryFileTree'][$strField] = clone $this->tree; // will be garbage collected

		$this->form->hidden($fieldName, $this->tree->selectedNode, 'id="' . $this->tree->receptorID . '"');
		if (is_array($this->tree->rootID)) {
			$start = NULL;
		} else {
			$start = $this->tree->rootID;
		}
		$this->tree->requestRoot = $start;
		$specificTree = $this->tree->getTreeInstance();
		$specificTree->id = $this->tree->rootID;  // array

		/* @var $specificTree LazyTreeBase */
		$this->form->text('
			<div class="jqueryFileTreeExtra" 
			containerID="' . $this->tree->containerID . '"
			receptorID="' . $this->tree->receptorID . '"
			strField="' . $strField . '"
			>
				<div>
					<img src="skin/default/img/down.gif" class="trigger" />
					<input id="' . $this->tree->receptorID . '_title" 
					value="' . $specificTree->getNameFor($this->tree->selectedNode) . '" 
					placeholder="Search starts after three letters"
					title="Search starts after three letters"
					onclick="jQuery(this).select()"
					type="search"
					/>
				</div>
				<div id="' . $this->tree->containerID . '" 
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
