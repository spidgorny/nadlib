<?php

class TYPO3ContentCollection extends Collection
{
	public $table = 'tt_content';
	public $idField = 'uid';
	public $orderBy = 'ORDER BY sorting';
	public $parentField = 'pid';
	public $where = [
		'hidden' => false,
		'deleted' => false,
	];

	/**
	 * @param string $class
	 * @param bool $byInstance
	 * @return TYPO3Content[]
	 */
	public function objectify($class = '', $byInstance = false)
	{
		return parent::objectify('TYPO3Content');
	}

}
