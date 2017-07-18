<?php

class TYPO3PageOverlay extends OODBase {
	var $table = 'pages_language_overlay';
	var $idField = 'uid';
	var $titleColumn = 'title';

	/**
	 * @var integer sys_language_uid
	 */
	public $langID;

	/**
	 * @var TYPO3ContentCollection
	 */
	public $content;

	public $colPos;

	function init($id, $fromFindInDB = false) {
		parent::init($id, $fromFindInDB);
		$this->langID = $this->data['sys_language_uid'];
	}

	function insert(array $data) {
		$data['tstamp'] = time();
		$data['crdate'] = time();
		return parent::insert($data);
	}

	function update(array $data) {
		$data['tstamp'] = time();
		return parent::insert($data);
	}

	function getAbstract() {
		return $this->data['abstract'];
	}

	function getDescription() {
		return $this->data['description'];
	}

	function getKeywords() {
		return $this->data['keywords'];
	}

	function fetchContent($colPos) {
		// retrieve once for each colPos
		if (!$this->content || $this->colPos != $colPos) {
			$this->content = new TYPO3ContentCollection($this->data['pid'], array(
				'colPos' => $colPos,
				'sys_language_uid' => Config::getInstance()->langID,
			));
			$this->content->objectify();	/* @var TYPO3Content */
			$this->colPos = $colPos;
		}
	}

	function getContent($colPos = 0) {
		$this->fetchContent($colPos);
		return $this->content->renderMembers();
	}

}
