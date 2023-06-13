<?php

use spidgorny\nadlib\HTTP\URL;

class TYPO3Page extends OODBase
{
	var $table = 'pages';
	var $idField = 'uid';
	var $titleColumn = 'title';

	/**
	 * @var TYPO3PageCollection
	 * NULL for initial check
	 */
	public $children = NULL;

	/**
	 * @var TYPO3ContentCollection
	 */
	public $content;

	/**
	 * Currently retrieved content column
	 *
	 * @var integer
	 */
	public $colPos;

	public function fetchChildren()
	{
		if (!$this->children) {
			$this->children = new TYPO3PageCollection($this->id);
			//debug($this->children->query);
		}
	}

	public function getChildren(array $where = [])
	{
		if (is_null($this->children)) {
			$this->fetchChildren();
		}
		return $this->children;
	}

	public function fetchContent($colPos)
	{
		// retrieve once for each colPos
		if (!$this->content || $this->colPos != $colPos) {
			$this->content = new TYPO3ContentCollection($this->id, [
				'colPos' => $colPos,
				'sys_language_uid' => 0,    // default, getPageOverlay() for other langs
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

	public function render()
	{
		return $this->getContent(0);
	}

	public function sidebar()
	{
		return $this->getContent(1);
	}

	public function getSlug()
	{
		return URL::friendlyURL($this->data['title']);
	}

	public function findDeepChild(array $match)
	{
		$this->fetchChildren();
		return $this->children->findDeepChild($match);
	}

	public function insert(array $data)
	{
		$data['tstamp'] = time();
		$data['crdate'] = time();
		$data['doktype'] = $data['doktype'] ? $data['doktype'] : 1;
		return parent::insert($data);
	}

	public function update(array $data)
	{
		$data['tstamp'] = time();
		return parent::update($data);
	}

	public function getAbstract()
	{
		return $this->data['abstract'];
	}

	public function getDescription()
	{
		//debug($this->data);
		return $this->data['description'];
	}

	public function getKeywords()
	{
		return $this->data['keywords'];
	}

	/**
	 * @return TYPO3PageOverlay|null
	 * @throws Exception
	 */
	public function getPageOverlay()
	{
		if (Config::getInstance()->langID) {
			$po = new TYPO3PageOverlay();
			$po->findInDB([
				'pid' => $this->id,
				'sys_language_uid' => Config::getInstance()->langID,
			]);
			return $po->id ? $po : null;
		}
		return null;
	}

	public function getParentPage()
	{
		if ($this->data['pid']) {
			$parent = TYPO3Page::getInstance($this->data['pid']);
			return $parent;
		}
		return null;
	}

	public function getLangAbstract()
	{
		$content = null;
		if (Config::getInstance()->langID) {
			$overlay = $this->getPageOverlay();
			if ($overlay) {
				$content = $overlay->getAbstract();
			}
		} else {
			$content = $this->getAbstract();        // default language
		}

		if (!$content) {
			$parent = $this->getParentPage();
			if ($parent) {
				$content = $parent->getLangAbstract();
			}
		}
		return $content;
	}

	public function getLangDescription()
	{
		$content = null;
		if (Config::getInstance()->langID) {
			$overlay = $this->getPageOverlay();
			if ($overlay) {
				$content = $overlay->getDescription();
			}
		} else {
			$content = $this->getDescription();        // default language
		}
		if (!$content) {
			$parent = $this->getParentPage();
			if ($parent) {
				$content = $parent->getLangDescription();
			}
		}
		return $content;
	}

	public function getLangKeywords()
	{
		$content = null;
		if (Config::getInstance()->langID) {
			$overlay = $this->getPageOverlay();
			if ($overlay) {
				$content = $overlay->getKeywords();
			}
		} else {
			$content = $this->getKeywords();        // default language
		}
		if (!$content) {
			$parent = $this->getParentPage();
			if ($parent) {
				$content = $parent->getLangKeywords();
			}
		}
		return $content;
	}

}
