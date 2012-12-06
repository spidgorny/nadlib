<?php

class TYPO3ContentCollection extends Collection {
	var $table = 'tt_content';
	var $idField = 'uid';
	var $orderBy = 'ORDER BY sorting';
	var $parentField = 'pid';
	var $where = array(
		'hidden' => false,
		'deleted' => false,
	);

	function objectify() {
		parent::objectify('TYPO3Content');
	}

}
