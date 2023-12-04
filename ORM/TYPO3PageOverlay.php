<?php

class TYPO3PageOverlay extends OODBase
{
	public $table = 'pages_language_overlay';
	public $idField = 'uid';
	public $titleColumn = 'title';

	/**
	 * @var int sys_language_uid
	 */
	public $langID;

	/**
	 * @var TYPO3ContentCollection
	 */
	public $content;

	public $colPos;

	public function init($id)
	{
		parent::init($id);
		$this->langID = $this->data['sys_language_uid'];
	}

	public function insert(array $data)
	{
		$data['tstamp'] = time();
		$data['crdate'] = time();
		return parent::insert($data);
	}

	public function update(array $data)
	{
		$data['tstamp'] = time();
		return parent::insert($data);
	}

	public function getAbstract()
	{
		return $this->data['abstract'];
	}

	public function getDescription()
	{
		return $this->data['description'];
	}

	public function getKeywords()
	{
		return $this->data['keywords'];
	}

	public function fetchContent($colPos)
	{
		// retrieve once for each colPos
		if (!$this->content || $this->colPos != $colPos) {
			$this->content = new TYPO3ContentCollection($this->data['pid'], [
				'colPos' => $colPos,
				'sys_language_uid' => Config::getInstance()->langID,
			]);
			$this->content->objectify();
			/* @var TYPO3Content */
			$this->colPos = $colPos;
		}
	}

	public function getContent($colPos = 0)
	{
		$this->fetchContent($colPos);
		return $this->content->renderMembers();
	}

}
