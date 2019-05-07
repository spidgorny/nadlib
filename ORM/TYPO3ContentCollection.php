<?php

class TYPO3ContentCollection extends Collection
{
	var $table = 'tt_content';
	var $idField = 'uid';
	var $orderBy = 'ORDER BY sorting';
	var $parentField = 'pid';
	var $where = array(
		'hidden' => false,
		'deleted' => false,
	);

	/**
	 * @param string $class
	 * @param bool $byInstance
	 * @return TYPO3Content[]
	 */
	function objectify($class = '', $byInstance = false)
	{
		return parent::objectify('TYPO3Content');
	}

}
