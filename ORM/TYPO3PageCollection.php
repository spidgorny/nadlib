<?php

class TYPO3PageCollection extends Collection
{
	var $table = 'pages';
	var $idField = 'uid';
	var $orderBy = 'ORDER BY sorting';
	var $parentField = 'pid';
	var $where = [
		'hidden' => false,
		'deleted' => false,
		'doktype' => 1,
	];

	function objectify($class = '', $byInstance = false)
	{
		parent::objectify('TYPO3Page', true);
	}

	function findDeepChild(array $match)
	{
		$ret = $this->findInData($match);
		$this->objectify();
		if ($ret) {
			//debug('found straight');
			$ret = $this->members[$ret[$this->idField]];    // get existing object
		} else {
			foreach ($this->members as $page) {
				/** @var TYPO3Page $page */
				$ret = $page->findDeepChild($match);
				if ($ret) {
					//debug('found inside');
					break;
				}
			}
		}
		return $ret;
	}

}
