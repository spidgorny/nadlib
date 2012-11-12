<?php

class TYPO3PageCollection extends Collection {
	var $table = 'pages';
	var $idField = 'uid';
	var $orderBy = 'ORDER BY sorting';
	var $parentField = 'pid';
	var $where = array(
		'hidden' => false,
		'deleted' => false,
		'doktype' => 1,
	);

}
