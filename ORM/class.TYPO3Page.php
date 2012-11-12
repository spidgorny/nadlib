<?php

class TYPO3Page extends OODBase {
	var $table = 'pages';
	var $idField = 'uid';
	var $titleColumn = 'title';

	/**
	 * @var TYPO3PageCollection
	 */
	public $children;

	/**
	 * @var TYPO3ContentCollection
	 */
	public $content;

	function fetchChildren() {
		$this->children = new TYPO3PageCollection($this->id);
	}

	function getContent($colPos = 0) {
		$this->content = new TYPO3ContentCollection($this->id, array(
			'colPos' => $colPos,
		));
		$this->content->objectify('TYPO3Content');	/* @var TYPO3Content */
		return $this->content->renderMembers();
	}

	function render() {
		return $this->getContent(0);
	}

	function sidebar() {
		return $this->getContent(1);
	}

	function getSlug() {
		return Controller::friendlyURL($this->data['title']);
	}

}
