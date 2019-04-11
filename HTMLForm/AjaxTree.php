<?php

class AjaxTree extends HTMLFormType implements HTMLFormTypeInterface
{

	/**
	 * @var TTree
	 */
	var $tree;

	function __construct(TTree $tree)
	{
		$this->setTree($tree);
		$this->setForm(new HTMLForm());
	}

	function setTree(TTree $tree)
	{
		$this->tree = $tree;
	}

	function render()
	{
		$tmp = $this->form->stdout;
		$this->form->stdout = '';

		$fieldName = $this->field;
		$tree = $this->tree;
		$desc = $this->desc;
		/* @var $tree TTree */
		$textName = $this->form->getName($fieldName, '', TRUE);

		$this->form->hidden($fieldName, $tree->selectedNode, 'id="treeNodeClickValue_' . $textName . '"');
		if (is_array($fieldName)) {
			$fieldNameName = $fieldName;
			$fieldNameName[sizeof($fieldName) - 1] = end($fieldName) . '_name';
		} else {
			$fieldNameName = $fieldName . '_name';
		}
		$value = $tree->getNameFor($tree->selectedNode);
		$this->form->input($fieldNameName,
			$value . ' (' . $tree->selectedNode . ')',
			[
				'style' => 'width: ' . ifsetor($desc['size'], '25em'),
				'readonly' => 'readonly',
				'id' => "treeNodeClickName_'.$textName.'"
			]);
		$this->form->text('&nbsp;');

		$tree->sessionID = $textName;
		$_SESSION['bijouTreeXML']['trees'][$textName] = clone $tree;
		if (!isset($_SESSION['bijouTreeXML']['trees'][$textName])) {
			print('$_SESSION[\'bijouTreeXML\'][\'trees\'][' . $textName . '] undefined' . BR);
		}

		$divID = $this->renderAjaxTreeToggle($fieldName, $tree);

		$content = $this->form->stdout;
		$this->form->stdout = $tmp;
		return $content;
	}

	/**
	 * Displays the project selection button image. Clicking it will open a div with a tree. Tree is the TTree object.
	 * Passing TTree objects means that the complete tree is to be retrieved in advance. But TTree class can be changed
	 * in a way, that it will contain only the necessary parameters to retrieve the tree - thus allowing lightweight
	 * object passing. This version also is NOT ajax. Div and Tree is rendered every-time. Please see if this is still
	 * true as this must changed ASAP.
	 * BTW, old $desc parameters must be a part of the lightweight TTree.
	 *
	 * $desc['selectID'] - <select name="projects" id="projects">
	 * $desc['treeDivID'] - <div id="treeDivID" style="display: none"></div>
	 * $desc['tableName'] - SELECT * FROM tableName ...
	 * $desc['tableRoot'] - ... WHERE pid = tableRoot
	 * $desc['tableTitle'] - SELECT id, tableTitle FROM ...
	 * $desc['paddedID'] - paddedID.innterHTML = tree.toString()
	 *
	 * @param string $fieldName
	 * @param TTree $tree
	 * @return integer div id of the windows which is hidden originally, this is used to hide it again after selecting an element
	 */
	function renderAjaxTreeToggle($fieldName, TTree $tree)
	{
		$textName = $this->form->getName($fieldName, '', TRUE);
		$caller = uniqid('img_');
		$id = /*uniqid*/
			('div_' . $textName); // to be reused in ajaxTreeInput onSuccess

		new treeArrayLoad(); // to include the corresponding javascript or the tree for use in ajax.
		$onclick = "return startAjaxTree('" . $id . "', '" . $textName . "', '" . $caller . "', '&jsCallbackFunction=treeNodeClick_Input')";
		$this->form->stdout .= new HTMLTag('a', [
			'href' => '',
			'onclick' => $onclick,
		], '<img src="skin/default/img/browsefolder.png" id="' . $caller . '" width="16" height="16">', true);

		// next three lines are commented, because they moved to bijouTreeXML.php?do=ajaxTreeStart
//		$t = new treeArrayLoad();
//		$t->buildTFromSession($this->getName($fieldName, '', TRUE), $tree);
//		$renderTree .= $t->getContent();
		//$renderTree .= view_array($tree->tree);
		$renderTree = '<img src="skin/default/img/progressBarShort.gif" />';

		$this->form->stdout .= '<div id="' . $id . '" class="htmlFormAjaxTree" style="display: none;">';
		$e = new AppController();
		$this->form->stdout .= $e->encloseIn('Select an element', $renderTree, TRUE, array(
			'closeButton' => $id,
			'innerStyle' => 'height: 400px; overflow: scroll;',
			'innerID' => 'inner_' . $textName,
			'unfoldable' => TRUE,
		));
		$this->form->stdout .= '</div>';
		return $id;
	}

}
