<?php

class TYPO3Content extends OODBase {
	var $table = 'tt_content';
	var $idField = 'uid';
	var $titleColumn = 'header';

	function __toString() {
		return $this->id.': '.$this->data['bodytext'].'<br>';
	}

}
